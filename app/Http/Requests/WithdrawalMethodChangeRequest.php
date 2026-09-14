<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * §56 — Demande de modification du moyen de retrait par défaut (avant
 * vérification OTP).
 */
class WithdrawalMethodChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAffiliate();
    }

    public function rules(): array
    {
        return [
            'method' => ['required', Rule::in(['mobile_money', 'bank_account', 'e_wallet'])],
            'mobile_money_number' => ['required_if:method,mobile_money', 'nullable', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'mobile_money_operator' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['required_if:method,bank_account', 'nullable', 'string', 'max:150'],
            'bank_account_number' => ['required_if:method,bank_account', 'nullable', 'string', 'max:50'],
            'bank_account_holder' => ['required_if:method,bank_account', 'nullable', 'string', 'max:150'],
            'e_wallet_provider' => ['required_if:method,e_wallet', 'nullable', 'string', 'max:100'],
            'e_wallet_id' => ['required_if:method,e_wallet', 'nullable', 'string', 'max:150'],
        ];
    }

    public function toAccountDetails(): array
    {
        $v = $this->validated();

        return match ($v['method']) {
            'mobile_money' => ['mobile_money_number' => $v['mobile_money_number'], 'operator' => $v['mobile_money_operator'] ?? null],
            'bank_account' => ['bank_name' => $v['bank_name'], 'account_number' => $v['bank_account_number'], 'account_holder' => $v['bank_account_holder']],
            'e_wallet' => ['provider' => $v['e_wallet_provider'], 'wallet_id' => $v['e_wallet_id']],
        };
    }
}
