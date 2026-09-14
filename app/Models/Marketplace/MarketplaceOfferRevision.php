<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MARKETPLACE — instantané du contenu d'une offre au moment d'une soumission.
 * La boutique publique lit toujours la ligne cœur ; cette table sert au
 * diff en modération et à conserver la version en ligne pendant la revue.
 */
class MarketplaceOfferRevision extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['offer_id', 'version', 'payload', 'submitted_by', 'submitted_at', 'state'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'submitted_at' => 'datetime', 'version' => 'integer'];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOffer::class, 'offer_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
