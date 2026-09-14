<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * MARKETPLACE — création / édition d'un champ personnalisé de catégorie.
 */
class CategoryFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'key' => Str::of($this->input('key') ?: $this->input('label', ''))->slug('_')->toString(),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $category = $this->route('category');
        $fieldId = $this->route('field')?->getKey();

        return [
            'key' => [
                'required', 'string', 'max:60', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('marketplace_category_fields', 'key')
                    ->where(fn ($q) => $q->where('category_id', $category?->getKey()))
                    ->ignore($fieldId),
            ],
            'label' => ['required', 'string', 'max:120'],
            'help_text' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:text,textarea,number,boolean,select,multiselect,date,url,file'],
            'options_text' => ['nullable', 'string', 'max:2000'],
            'is_required' => ['boolean'],
            'is_filterable' => ['boolean'],
            'is_variant_axis' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'validation' => ['nullable', 'array'],
            'validation.min' => ['nullable', 'numeric'],
            'validation.max' => ['nullable', 'numeric'],
            'validation.regex' => ['nullable', 'string', 'max:200'],
        ];
    }

    /** @return array<string, mixed> */
    public function forModel(): array
    {
        $type = $this->input('type');

        $options = null;
        if (in_array($type, ['select', 'multiselect'], true)) {
            $options = collect(preg_split('/\r\n|\r|\n/', (string) $this->input('options_text')))
                ->map(fn ($l) => trim($l))
                ->filter()
                ->map(fn ($l) => ['value' => Str::slug($l, '_'), 'label' => $l])
                ->values()
                ->all();
        }

        $validation = array_filter([
            'min' => $this->input('validation.min'),
            'max' => $this->input('validation.max'),
            'regex' => $this->input('validation.regex') ?: null,
        ], fn ($v) => $v !== null && $v !== '');

        return [
            'key' => $this->input('key'),
            'label' => $this->input('label'),
            'help_text' => $this->input('help_text') ?: null,
            'type' => $type,
            'options' => $options,
            'validation' => $validation ?: null,
            'is_required' => $this->boolean('is_required'),
            'is_filterable' => $this->boolean('is_filterable'),
            'is_variant_axis' => $type === 'select' ? $this->boolean('is_variant_axis') : false,
            'position' => (int) ($this->input('position') ?? 0),
        ];
    }
}
