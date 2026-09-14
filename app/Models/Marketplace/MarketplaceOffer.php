<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MARKETPLACE — offre d'un vendeur (table cœur).
 */
class MarketplaceOffer extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const TYPES = ['physical', 'digital', 'service', 'training'];

    /** Statuts pour lesquels le vendeur peut encore éditer librement. */
    public const EDITABLE_STATUSES = ['draft', 'changes_requested', 'rejected'];

    protected $fillable = [
        'vendor_profile_id',
        'category_id',
        'type',
        'title',
        'slug',
        'summary',
        'description',
        'price',
        'sale_price',
        'currency',
        'main_image_path',
        'gallery',
        'custom_fields',
        'status',
        'is_online',
        'version',
        'submitted_at',
        'approved_at',
        'approved_by',
        'first_published_at',
        'rejection_reason',
        'review_note',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'gallery' => 'array',
            'custom_fields' => 'array',
            'is_online' => 'boolean',
            'version' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'first_published_at' => 'datetime',
            'rating_avg' => 'decimal:2',
            'rating_count' => 'integer',
            'sales_count' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class, 'vendor_profile_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'category_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function physical(): HasOne
    {
        return $this->hasOne(MarketplaceOfferPhysical::class, 'offer_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MarketplaceOfferVariant::class, 'offer_id')
            ->orderBy('position')->orderBy('created_at');
    }

    public function hasVariants(): bool
    {
        return $this->type === 'physical'
            && ($this->relationLoaded('variants') ? $this->variants->isNotEmpty() : $this->variants()->exists());
    }

    public function digital(): HasOne
    {
        return $this->hasOne(MarketplaceOfferDigital::class, 'offer_id');
    }

    public function service(): HasOne
    {
        return $this->hasOne(MarketplaceOfferService::class, 'offer_id');
    }

    public function training(): HasOne
    {
        return $this->hasOne(MarketplaceOfferTraining::class, 'offer_id');
    }

    public function detail(): ?Model
    {
        return $this->{$this->type} ?? null;
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(MarketplaceOfferRevision::class, 'offer_id')->latest('version');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MarketplaceOfferReview::class, 'offer_id')->latest();
    }

    /** Avis publics des acheteurs (à ne pas confondre avec les décisions de modération). */
    public function buyerReviews(): HasMany
    {
        return $this->hasMany(MarketplaceReview::class, 'offer_id');
    }

    /** Recalcule la note moyenne dénormalisée à partir des avis publiés. */
    public function recomputeRating(): void
    {
        $published = $this->buyerReviews()->where('status', 'published');

        $this->forceFill([
            'rating_count' => $published->count(),
            'rating_avg' => round((float) $published->avg('rating'), 2),
        ])->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** Approuvée ET mise en ligne par le vendeur. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'approved')->where('is_online', true);
    }

    /**
     * Réellement visible du public : en ligne ET portée par un vendeur actif.
     * C'est le scope à utiliser partout côté boutique.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->live()->whereHas('vendor', fn (Builder $q) => $q->where('status', 'active'));
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('status', 'pending_review');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isEditableByVendor(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES, true);
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this->status, ['draft', 'changes_requested', 'rejected'], true);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function effectivePrice(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }
}
