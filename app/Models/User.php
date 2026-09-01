<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'country',
        'currency',
        'role',
        'affiliate_id',
        'code_parrainage',
        'parent_id',
        'affiliate_activated_at',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'affiliate_activated_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Portefeuille de l'utilisateur (§43). Un User a un seul Wallet.
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Parrain direct (§35). Une fois rattaché, ne doit plus être modifiable
     * par l'affilié lui-même — règle appliquée au niveau du Service, pas ici.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Filleuls directs de niveau 1 (§36-§37).
     */
    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * Commandes passées par ce user en tant que client.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Commandes générées par ce user en tant qu'affilié référent (§39).
     */
    public function referredOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'referring_affiliate_id');
    }

    /**
     * Toutes les écritures du ledger concernant ce user (commissions, retraits...).
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * Ajustements manuels effectués par ce user en tant qu'administrateur (§49).
     */
    public function adminAdjustments(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'admin_id');
    }

    /**
     * Historique des demandes de retrait de ce user (§50, §58).
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers métier
    |--------------------------------------------------------------------------
    */

    public function isAffiliate(): bool
    {
        return ! is_null($this->affiliate_id);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
