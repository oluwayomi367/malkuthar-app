<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validation création / modification d'un produit par l'administrateur
 * (§6-§18). Fait respecter la RÈGLE ABSOLUE §4 : seul un produit
 * type=digital peut être affiliable.
 */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->getKey();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('products', 'slug')->ignore($productId)],
            'description' => ['nullable', 'string', 'max:20000'],
            'type' => ['required', Rule::in(['digital', 'physical', 'service'])],
            'is_affiliable' => ['boolean'],

            'price' => ['required', 'numeric', 'min:0'],
            'promotional_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'currency' => ['required', 'string', 'size:3'],

            'category' => ['nullable', 'string', 'max:255'],
            'sub_category' => ['nullable', 'string', 'max:255'],
            'presentation_video' => ['nullable', 'string', 'max:2048'],

            // Images (§13) — upload direct depuis le formulaire.
            'main_image_file' => array_merge(['nullable'], $this->imageFileRules()),
            'remove_main_image' => ['nullable', 'boolean'],
            'gallery_files' => ['nullable', 'array', 'max:8'],
            'gallery_files.*' => $this->imageFileRules(),
            'gallery_remove' => ['nullable', 'array'],
            'gallery_remove.*' => ['string'],

            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'is_available' => ['boolean'],

            // DIGITAL (§7, §9)
            'file_path' => ['nullable', 'string', 'max:2048', 'required_if:type,digital'],

            // PHYSICAL (§10-§12)
            'sku' => ['nullable', 'string', 'max:255', 'required_if:type,physical', Rule::unique('products', 'sku')->ignore($productId)],
            'stock_available' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],

            // SERVICE (§16-§18) — la durée est gérée dans le panneau
            // « Calendrier » (endpoint schedule) ; on garde une borne ici.
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'location' => ['nullable', 'string', 'max:255'],
            'delivery_mode' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:2048'],
        ];
    }

    /**
     * Règles d'un fichier image, sans dépendre du sniffing MIME de PHP
     * (`fileinfo`/libmagic) qui identifie mal WebP/AVIF sur certaines
     * plateformes → l'ancien `image`/`mimes` rejetait des images valides.
     *
     * @return array<int, mixed>
     */
    private function imageFileRules(): array
    {
        return [
            'file',
            'max:5120',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! $value instanceof UploadedFile) {
                    return;
                }

                $ext = strtolower($value->getClientOriginalExtension());
                $allowed = ['jpg', 'jpeg', 'jpe', 'jfif', 'png', 'webp', 'avif', 'gif'];

                if (! in_array($ext, $allowed, true)) {
                    $fail('Le fichier doit être une image JPG, PNG, GIF, WebP ou AVIF.');

                    return;
                }

                // Contrôle du contenu quand PHP sait lire l'en-tête. L'AVIF
                // peut renvoyer false selon la version de PHP : on lui laisse
                // le bénéfice du doute (l'extension a déjà été validée).
                if ($ext !== 'avif' && $value->isValid() && @getimagesize($value->getRealPath()) === false) {
                    $fail("« {$value->getClientOriginalName()} » n'est pas une image valide.");
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_affiliable' => $this->boolean('is_affiliable'),
            'is_available' => $this->boolean('is_available'),
            'currency' => $this->input('currency') ?: 'XOF',
        ]);
    }

    /**
     * Un <input type="file"> laissé vide arrive quand même dans la requête
     * sous la forme d'un UploadedFile invalide (UPLOAD_ERR_NO_FILE). On le
     * retire des données validées : sinon `main_image_file` / `gallery_files.*`
     * échouent (« failed to upload ») alors que l'admin n'a rien téléversé —
     * il veut par exemple seulement RETIRER une image.
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        $data = parent::validationData();

        if (array_key_exists('main_image_file', $data) && ! $this->isRealUpload($data['main_image_file'])) {
            unset($data['main_image_file']);
        }

        if (array_key_exists('gallery_files', $data)) {
            $data['gallery_files'] = array_values(array_filter(
                (array) $data['gallery_files'],
                fn ($file) => $this->isRealUpload($file),
            ));

            if ($data['gallery_files'] === []) {
                unset($data['gallery_files']);
            }
        }

        return $data;
    }

    private function isRealUpload(mixed $file): bool
    {
        return $file instanceof UploadedFile && $file->isValid();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($this->boolean('is_affiliable') && $this->input('type') !== 'digital') {
                $v->errors()->add('is_affiliable', 'Seul un produit digital peut être affiliable (§4).');
            }
        });
    }

    /**
     * Normalise les champs incohérents avec le type retenu avant persistance.
     *
     * @param  array<string, mixed>|int|string|null  $key
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated();

        if (($data['type'] ?? null) !== 'digital') {
            $data['is_affiliable'] = false;
        }

        return $data;
    }
}
