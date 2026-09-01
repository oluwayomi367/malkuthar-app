<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * CheckoutRequest — "Checkout intelligent" (§21 du cahier des charges).
 *
 * Le panier envoyé peut contenir un mélange de produits DIGITAL, PHYSICAL
 * et SERVICE. Les champs exigés varient dynamiquement selon les types
 * réellement présents :
 *
 *  - Panier digital uniquement -> l'email suffit, pas d'adresse.
 *  - Présence d'un produit PHYSICAL -> adresse de livraison obligatoire.
 *  - Présence d'un SERVICE -> date + heure de réservation obligatoires
 *    (uniquement sur la ligne concernée).
 *  - Panier mixte -> seules les informations pertinentes sont demandées.
 */
class CheckoutRequest extends FormRequest
{
    /**
     * Produits chargés depuis la base, indexés par id, pour connaître leur
     * type sans requêter la BDD plusieurs fois pendant la validation.
     */
    protected Collection $productsById;

    protected bool $hasDigital = false;

    protected bool $hasPhysical = false;

    protected bool $hasService = false;

    public function authorize(): bool
    {
        // Le checkout est ouvert aux clients connectés et aux invités
        // (§77 - un client peut consulter son compte, mais l'achat digital
        // avec livraison automatique par email reste possible sans compte
        // selon le parcours retenu). Ajuster ici si le compte devient obligatoire.
        return true;
    }

    /**
     * Précharge les produits du panier et détecte les types présents
     * AVANT que rules() ne soit évalué, afin de construire des règles
     * conditionnelles fiables.
     */
    protected function prepareForValidation(): void
    {
        $productIds = collect($this->input('items', []))
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        $this->productsById = Product::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $types = $this->productsById->pluck('type');

        $this->hasDigital = $types->contains('digital');
        $this->hasPhysical = $types->contains('physical');
        $this->hasService = $types->contains('service');
    }

    public function rules(): array
    {
        $rules = [
            // §26 - le moyen/prestataire de paiement est choisi au checkout,
            // la confirmation réelle se fait ensuite côté serveur (webhook).
            'payment_method' => ['required', 'string', 'max:50'],
            'payment_provider' => ['required', 'string', Rule::in(['fedapay', 'cinetpay'])],

            'coupon_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // §33 - tracking d'un éventuel lien d'affiliation actif sur la session
            'referral_code' => ['nullable', 'string', 'max:50'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists('products', 'id'),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.variant_sku' => ['nullable', 'string', 'max:100'],
        ];

        // --- Email : toujours requis, panier digital ou non (§21) ---
        // Si un client est authentifié, on retombe sur son email de compte
        // sans redemander la saisie.
        $rules['email'] = $this->user()
            ? ['sometimes', 'nullable', 'email', 'max:255']
            : ['required', 'email', 'max:255'];

        // --- Produit PHYSICAL présent -> adresse de livraison obligatoire (§13, §21) ---
        if ($this->hasPhysical) {
            $rules = array_merge($rules, [
                'shipping_address' => ['required', 'array'],
                'shipping_address.first_name' => ['required', 'string', 'max:255'],
                'shipping_address.last_name' => ['required', 'string', 'max:255'],
                'shipping_address.phone' => ['required', 'string', 'max:30'],
                'shipping_address.country' => ['required', 'string', 'max:100'],
                'shipping_address.city' => ['required', 'string', 'max:100'],
                'shipping_address.neighborhood' => ['nullable', 'string', 'max:150'],
                'shipping_address.address' => ['required', 'string', 'max:500'],
                'shipping_address.relay_point' => ['nullable', 'string', 'max:255'],
                'shipping_address.delivery_instructions' => ['nullable', 'string', 'max:500'],
            ]);
        }

        // --- Produit SERVICE présent -> date/heure de réservation obligatoires
        // UNIQUEMENT sur les lignes concernées (§17, §21) ---
        if ($this->hasService) {
            foreach ((array) $this->input('items', []) as $index => $item) {
                $productId = $item['product_id'] ?? null;
                $product = $productId ? $this->productsById->get($productId) : null;

                if ($product && $product->type === 'service') {
                    $rules["items.$index.reservation_date"] = ['required', 'date_format:Y-m-d', 'after_or_equal:today'];
                    $rules["items.$index.reservation_time"] = ['required', 'date_format:H:i'];
                }
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Le panier ne peut pas être vide.',
            'items.*.product_id.exists' => 'Un des produits du panier est introuvable.',
            'shipping_address.required' => 'Votre panier contient un produit physique : l\'adresse de livraison est obligatoire.',
            'shipping_address.address.required' => 'L\'adresse de livraison est obligatoire.',
            '*.reservation_date.required' => 'La date de réservation est obligatoire pour ce service.',
            '*.reservation_time.required' => 'L\'heure de réservation est obligatoire pour ce service.',
            'email.required' => 'Une adresse email est nécessaire pour recevoir la confirmation de commande.',
        ];
    }

    public function attributes(): array
    {
        return [
            'shipping_address.first_name' => 'prénom',
            'shipping_address.last_name' => 'nom',
            'shipping_address.phone' => 'téléphone',
            'shipping_address.country' => 'pays',
            'shipping_address.city' => 'ville',
            'shipping_address.address' => 'adresse',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers exposés au Controller / Service
    |--------------------------------------------------------------------------
    */

    public function hasDigitalItems(): bool
    {
        return $this->hasDigital;
    }

    public function hasPhysicalItems(): bool
    {
        return $this->hasPhysical;
    }

    public function hasServiceItems(): bool
    {
        return $this->hasService;
    }

    public function isDigitalOnlyCart(): bool
    {
        return $this->hasDigital && ! $this->hasPhysical && ! $this->hasService;
    }

    /**
     * @return Collection<string, Product>
     */
    public function productsById(): Collection
    {
        return $this->productsById;
    }
}
