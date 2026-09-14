<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Message envoyé depuis la page /contact.
 */
class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', Rule::in(['commande', 'produit', 'affiliation', 'vendeur', 'autre'])],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
            // Piège à robots : doit rester vide.
            'website' => ['nullable', 'prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.min' => 'Merci de détailler un peu votre demande (10 caractères minimum).',
            'website.prohibited' => 'Une erreur est survenue. Réessayez.',
        ];
    }

    public function subjectLabel(): string
    {
        return [
            'commande' => 'Une commande',
            'produit' => 'Un produit',
            'affiliation' => 'Le programme d\'affiliation',
            'vendeur' => 'Devenir vendeur (Marketplace)',
            'autre' => 'Autre',
        ][$this->validated()['subject']] ?? 'Autre';
    }
}
