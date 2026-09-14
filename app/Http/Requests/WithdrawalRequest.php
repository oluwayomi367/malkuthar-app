<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * WithdrawalRequest — validation d'une demande de retrait affilié (§50,
 * §52 : plusieurs moyens de retrait, pas un seul opérateur/pays).
 */
class WithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seul un affilié authentifié peut demander un retrait sur son
        // propre wallet (§50).
        return (bool) $this->user()?->isAffiliate();
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', Rule::in(['mobile_money', 'bank_account', 'e_wallet'])],

            // Mobile Money
            'mobile_money_number' => ['required_if:method,mobile_money', 'nullable', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'mobile_money_operator' => ['nullable', 'string', 'max:50'],

            // Compte bancaire
            'bank_name' => ['required_if:method,bank_account', 'nullable', 'string', 'max:150'],
            'bank_account_number' => ['required_if:method,bank_account', 'nullable', 'string', 'max:50'],
            'bank_account_holder' => ['required_if:method,bank_account', 'nullable', 'string', 'max:150'],

            // Portefeuille électronique générique
            'e_wallet_provider' => ['required_if:method,e_wallet', 'nullable', 'string', 'max:100'],
            'e_wallet_id' => ['required_if:method,e_wallet', 'nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'Le montant du retrait doit être supérieur à zéro.',
            'mobile_money_number.regex' => 'Le numéro Mobile Money saisi est invalide.',
        ];
    }

    public function toWithdrawalData(): array
    {
        $validated = $this->validated();

        $accountDetails = match ($validated['method']) {
            'mobile_money' => [
                'mobile_money_number' => $validated['mobile_money_number'],
                'operator' => $validated['mobile_money_operator'] ?? null,
            ],
            'bank_account' => [
                'bank_name' => $validated['bank_name'],
                'account_number' => $validated['bank_account_number'],
                'account_holder' => $validated['bank_account_holder'],
            ],
            'e_wallet' => [
                'provider' => $validated['e_wallet_provider'],
                'wallet_id' => $validated['e_wallet_id'],
            ],
        };

        return [
            'amount' => (float) $validated['amount'],
            'method' => $validated['method'],
            'account_details' => $accountDetails,
        ];
    }
}
