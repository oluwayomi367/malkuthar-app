<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MARKETPLACE — profil praticien / vendeur. Porte l'habilitation marketplace
 * d'un compte `users` (qui peut par ailleurs être client et affilié).
 */
class VendorProfile extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'display_name',
        'slug',
        'legal_name',
        'bio',
        'logo_path',
        'banner_path',
        'region',
        'country',
        'city',
        'contact_email',
        'phone',
        'status',
        'is_trusted',
        'commission_rate_override',
        'payout_currency',
        'approved_by',
        'approved_at',
        'suspended_at',
        'suspension_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_trusted' => 'boolean',
            'commission_rate_override' => 'decimal:4',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function balance(): HasOne
    {
        return $this->hasOne(VendorBalance::class, 'vendor_profile_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(VendorLedgerEntry::class, 'vendor_profile_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class, 'vendor_profile_id');
    }

    public function payoutAccounts(): HasMany
    {
        return $this->hasMany(MarketplacePayoutAccount::class, 'vendor_profile_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
