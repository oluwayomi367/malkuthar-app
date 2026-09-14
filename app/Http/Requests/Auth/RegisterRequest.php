<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Inscription d'un compte MALKUTHAR (§30, §77). Le compte est unique :
 * il porte l'espace client et, après activation, l'espace affilié.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', Rule::in(config('malkuthar.i18n.supported_currencies', ['XOF']))],

            // §35 — code de parrainage éventuellement transmis dans le
            // formulaire (le cookie /ref/{code} reste la source principale).
            'referral_code' => ['nullable', 'string', 'max:50'],
        ];
    }
}
