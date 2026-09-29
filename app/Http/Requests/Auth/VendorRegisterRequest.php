<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class VendorRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Compte utilisateur
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:50'],
            'currency' => ['nullable', 'string', 'in:XOF,EUR,USD'],
            'referral_code' => ['nullable', 'string', 'max:50'],

            // Profil boutique vendeur
            'business_sector' => ['nullable', 'string', 'in:fashion_beauty,real_estate_hospitality,services_wellness,digital_academy'],
            'display_name' => ['required', 'string', 'min:2', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:150'],
            'bio' => ['required', 'string', 'min:10', 'max:2000'],
            'region' => ['required', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'payout_currency' => ['nullable', 'string', 'in:XOF,EUR,USD'],
            'accept_terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'business_sector.required' => 'Veuillez sélectionner votre secteur principal d\'activité.',
            'display_name.required' => 'Le nom de votre boutique est requis.',
            'bio.required' => 'Veuillez décrire votre activité et vos produits en quelques phrases.',
            'bio.min' => 'La présentation de votre activité doit comporter au moins 10 caractères.',
            'region.required' => 'La région d\'implantation est requise.',
            'accept_terms.accepted' => 'Vous devez accepter les conditions du programme vendeur pour continuer.',
        ];
    }
}
