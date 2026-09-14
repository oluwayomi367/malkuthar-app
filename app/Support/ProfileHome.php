<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Point d'entrée par profil : où atterrit un utilisateur après connexion /
 * inscription, et cible du raccourci « Mon espace ».
 *
 * Priorité : administrateur ▸ vendeur actif ▸ candidature vendeur en cours ▸
 * affilié ▸ espace client.
 */
class ProfileHome
{
    public static function routeName(User $user): string
    {
        if ($user->isAdmin()) {
            return 'admin.dashboard';
        }

        $vendor = $user->relationLoaded('vendorProfile')
            ? $user->vendorProfile
            : $user->vendorProfile()->first();

        if ($vendor && Route::has('vendor.dashboard')) {
            return $vendor->status === 'active' ? 'vendor.dashboard' : 'vendor.application.status';
        }

        if ($user->isAffiliate()) {
            return 'affiliate.dashboard';
        }

        return 'account.dashboard';
    }

    public static function url(User $user): string
    {
        return route(self::routeName($user));
    }
}
