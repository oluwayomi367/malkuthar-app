<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * WithdrawalRequest — validation d'une demande de retrait affilié (§50).
 * Cette version couvre le moyen Mobile Money ; à étendre avec
 * Rule::requiredIf() pour les autres méthodes (§52 - compte bancaire,
 * portefeuille électronique) sans casser la structure existante.
 */
class WithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seul un affilié authentifié peut demander un retrait sur son
        // propre wallet (§50 - "L'affilié devra pouvoir demander lui-même
        // un retrait depuis son espace").
        return (bool) $this->user()?->isAffiliate();
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],

            'method' => ['sometimes', Rule::in(['mobile_money'])],

            'mobile_money_number' => [
                'required',
                'string',
                'regex:/^\+?[0-9]{8,15}$/',
            ],
            'mobile_money_operator' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'Le montant du retrait doit être supérieur à zéro.',
            'mobile_money_number.regex' => 'Le numéro Mobile Money saisi est invalide.',
        ];
    }

    /**
     * Normalise les données pour le Service (méthode fixée à mobile_money
     * pour cette version du contrôleur).
     */
    public function toWithdrawalData(): array
    {
        $validated = $this->validated();

        return [
            'amount' => (float) $validated['amount'],
            'method' => 'mobile_money',
            'account_details' => [
                'mobile_money_number' => $validated['mobile_money_number'],
                'operator' => $validated['mobile_money_operator'] ?? null,
            ],
        ];
    }
}
