<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * MARKETPLACE — demande d'ouverture d'un espace vendeur.
 */
class VendorApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'utilisateur doit être connecté (middleware `auth`) et ne pas déjà
        // avoir un profil vendeur.
        return $this->user() !== null && ! $this->user()->vendorProfile()->exists();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'min:2', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:160'],
            'bio' => ['required', 'string', 'min:30', 'max:2000'],
            'region' => ['required', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'payout_currency' => ['nullable', 'string', 'size:3'],
            'accept_terms' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'bio.min' => 'Présentez votre activité en quelques phrases (30 caractères minimum).',
            'accept_terms.accepted' => 'Vous devez accepter les conditions vendeurs pour continuer.',
        ];
    }
}
