<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MARKETPLACE — décision de modération (append-only : jamais mise à jour).
 */
class MarketplaceOfferReview extends Model
{
    use HasFactory, HasUuids;

    const UPDATED_AT = null;

    protected $fillable = ['offer_id', 'offer_version', 'reviewer_id', 'decision', 'note', 'checklist'];

    protected function casts(): array
    {
        return ['checklist' => 'array', 'offer_version' => 'integer', 'created_at' => 'datetime'];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOffer::class, 'offer_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
