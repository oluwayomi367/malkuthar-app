<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MARKETPLACE — définition d'un champ personnalisé rattaché à une catégorie.
 */
class MarketplaceCategoryField extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'category_id',
        'key',
        'label',
        'help_text',
        'type',
        'options',
        'validation',
        'is_required',
        'is_filterable',
        'is_variant_axis',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'validation' => 'array',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'is_variant_axis' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'category_id');
    }

    /** Types dont la valeur est une liste. */
    public function isMultiValue(): bool
    {
        return $this->type === 'multiselect';
    }

    /** Valeurs admises pour un champ select / multiselect. */
    public function optionValues(): array
    {
        return array_map(
            static fn ($o) => is_array($o) ? ($o['value'] ?? '') : $o,
            $this->options ?? [],
        );
    }

    /**
     * Règles de validation Laravel pour la valeur scalaire de ce champ
     * (clé `custom_fields.{key}` d'un Form Request). Les contraintes sur les
     * éléments d'un multiselect sont gérées séparément par l'OfferValidator.
     *
     * @return array<int, string>
     */
    public function validationRules(): array
    {
        $rules = [$this->is_required ? 'required' : 'nullable'];
        $v = $this->validation ?? [];

        $rules[] = match ($this->type) {
            'number' => 'numeric',
            'boolean' => 'boolean',
            'date' => 'date',
            'url' => 'url',
            'multiselect' => 'array',
            default => 'string',
        };

        if ($this->type === 'select' && $this->options) {
            $rules[] = 'in:'.implode(',', $this->optionValues());
        }
        if (isset($v['min'])) {
            $rules[] = "min:{$v['min']}";
        }
        if (isset($v['max'])) {
            $rules[] = "max:{$v['max']}";
        }
        if (isset($v['regex'])) {
            $rules[] = "regex:{$v['regex']}";
        }

        return $rules;
    }
}
