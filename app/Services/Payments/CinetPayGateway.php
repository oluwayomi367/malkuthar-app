<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Payments\PaymentSession;
use App\Payments\PaymentVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * §22 — Implémentation CinetPay (API REST /v2) du contrat PaymentGateway.
 *
 * La référence de transaction est ici NOTRE `order_number` (CinetPay
 * accepte un transaction_id fourni par le marchand), ce qui simplifie la
 * réconciliation via webhook.
 */
class CinetPayGateway implements PaymentGateway
{
    private function baseUrl(): string
    {
        return rtrim((string) config('services.cinetpay.base_url', 'https://api-checkout.cinetpay.com/v2'), '/');
    }

    public function provider(): string
    {
        return 'cinetpay';
    }

    public function createCheckoutSession(Order $order, array $customer): PaymentSession
    {
        $payload = [
            'apikey' => (string) config('services.cinetpay.api_key'),
            'site_id' => (string) config('services.cinetpay.site_id'),
            'transaction_id' => $order->order_number,
            'amount' => (int) round((float) $order->total_amount),
            'currency' => $order->currency,
            'description' => 'Commande '.$order->order_number,
            'notify_url' => route('webhooks.psp', ['provider' => 'cinetpay']),
            'return_url' => route('checkout.return', ['order' => $order->id]),
            'channels' => 'ALL',
            'metadata' => 'order:'.$order->id,
            'customer_email' => $customer['email'] ?? null,
            'customer_name' => $customer['last_name'] ?? null,
            'customer_surname' => $customer['first_name'] ?? null,
        ];

        $response = Http::acceptJson()
            ->post($this->baseUrl().'/payment', array_filter($payload, fn ($v) => $v !== null))
            ->throw()
            ->json();

        if ((string) ($response['code'] ?? '') !== '201') {
            Log::error('CinetPayGateway: initiation refusée.', ['response' => $response]);
            throw new \RuntimeException('CinetPay a refusé l\'initiation du paiement: '.($response['description'] ?? 'inconnue'));
        }

        return new PaymentSession(
            provider: $this->provider(),
            reference: $order->order_number,
            paymentUrl: (string) ($response['data']['payment_url'] ?? ''),
            raw: ['payment_token' => $response['data']['payment_token'] ?? null],
        );
    }

    public function verifyTransaction(string $reference): PaymentVerification
    {
        $response = Http::acceptJson()
            ->post($this->baseUrl().'/payment/check', [
                'apikey' => (string) config('services.cinetpay.api_key'),
                'site_id' => (string) config('services.cinetpay.site_id'),
                'transaction_id' => $reference,
            ])
            ->throw()
            ->json();

        $data = $response['data'] ?? [];
        $cpStatus = strtoupper((string) ($data['status'] ?? ''));

        $status = match ($cpStatus) {
            'ACCEPTED' => PaymentVerification::PAID,
            'REFUNDED' => PaymentVerification::REFUNDED,
            'REFUSED', 'CANCELLED', 'FAILED' => PaymentVerification::FAILED,
            default => PaymentVerification::PENDING,
        };

        return new PaymentVerification(
            status: $status,
            reference: $reference,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            currency: $data['currency'] ?? null,
            metadata: ['raw_metadata' => $data['metadata'] ?? null],
            raw: $response,
        );
    }

    public function parseWebhook(string $rawPayload, array $headers): ?string
    {
        // CinetPay POSTe des champs de formulaire ; la valeur qui nous
        // intéresse est cpm_trans_id. L'authenticité réelle est établie
        // par la ré-interrogation de l'API (§26) ; on vérifie en plus le
        // token HMAC quand une clé secrète est configurée.
        parse_str($rawPayload, $fields);

        $reference = $fields['cpm_trans_id'] ?? null;

        if (! $reference) {
            return null;
        }

        $secret = (string) config('services.cinetpay.secret_key');
        $token = $headers['x-token'][0] ?? ($fields['cpm_token'] ?? null);

        if ($secret !== '' && $token) {
            $expected = hash_hmac('sha256', $this->tokenPayload($fields), $secret);
            if (! hash_equals($expected, (string) $token)) {
                Log::warning('CinetPayGateway: token webhook invalide.', ['reference' => $reference]);

                return null;
            }
        }

        return (string) $reference;
    }

    private function tokenPayload(array $f): string
    {
        // Ordre des champs imposé par la documentation CinetPay.
        return implode('', [
            $f['cpm_site_id'] ?? '', $f['cpm_trans_id'] ?? '', $f['cpm_trans_date'] ?? '',
            $f['cpm_amount'] ?? '', $f['cpm_currency'] ?? '', $f['signature'] ?? '',
            $f['payment_method'] ?? '', $f['cel_phone_num'] ?? '', $f['cpm_phone_prefixe'] ?? '',
            $f['cpm_language'] ?? '', $f['cpm_version'] ?? '', $f['cpm_payment_config'] ?? '',
            $f['cpm_page_action'] ?? '', $f['cpm_custom'] ?? '', $f['cpm_designation'] ?? '',
            $f['cpm_error_message'] ?? '',
        ]);
    }
}
