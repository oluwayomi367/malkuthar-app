<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\VendorProfile;
use App\Models\User;
use App\Notifications\Marketplace\VendorAccountSuspended;
use App\Notifications\Marketplace\VendorApplicationApproved;
use App\Notifications\Marketplace\VendorApplicationRejected;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * MARKETPLACE — cycle de vie du compte vendeur : candidature, validation
 * par un administrateur, suspension, réactivation.
 *
 * Toutes les transitions passent par ici : audit systématique (§68) et
 * notification du vendeur. Jamais d'écriture directe de `status` ailleurs.
 */
class VendorOnboardingService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Un utilisateur authentifié demande l'ouverture d'un espace vendeur.
     *
     * @param  array<string, mixed>  $data
     */
    public function submitApplication(User $user, array $data): VendorProfile
    {
        if ($user->vendorProfile()->exists()) {
            return $user->vendorProfile;
        }

        $profile = new VendorProfile([
            'display_name' => $data['display_name'],
            'slug' => $this->uniqueSlug($data['display_name']),
            'legal_name' => $data['legal_name'] ?? null,
            'bio' => $data['bio'] ?? null,
            'region' => $data['region'] ?? null,
            'country' => $data['country'] ?? null,
            'city' => $data['city'] ?? null,
            'contact_email' => $data['contact_email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'payout_currency' => $data['payout_currency'] ?? $user->currency ?? 'XOF',
            'metadata' => $data['metadata'] ?? null,
            'status' => 'pending',
        ]);
        $profile->user()->associate($user);
        $profile->save();

        $this->audit->record($user, 'marketplace.vendor.applied', $profile, [
            'display_name' => $profile->display_name,
        ]);

        return $profile;
    }

    public function approve(VendorProfile $profile, User $admin): VendorProfile
    {
        return DB::transaction(function () use ($profile, $admin) {
            $profile->forceFill([
                'status' => 'active',
                'approved_by' => $admin->getKey(),
                'approved_at' => now(),
                'suspended_at' => null,
                'suspension_reason' => null,
            ])->save();

            $this->audit->record($admin, 'marketplace.vendor.approved', $profile);
            $profile->user->notify(new VendorApplicationApproved($profile));

            return $profile;
        });
    }

    public function reject(VendorProfile $profile, User $admin, string $reason): VendorProfile
    {
        return DB::transaction(function () use ($profile, $admin, $reason) {
            $profile->forceFill([
                'status' => 'closed',
                'suspension_reason' => $reason,
            ])->save();

            $this->audit->record($admin, 'marketplace.vendor.rejected', $profile, ['reason' => $reason]);
            $profile->user->notify(new VendorApplicationRejected($profile, $reason));

            return $profile;
        });
    }

    public function suspend(VendorProfile $profile, User $admin, string $reason): VendorProfile
    {
        return DB::transaction(function () use ($profile, $admin, $reason) {
            $profile->forceFill([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ])->save();

            $this->audit->record($admin, 'marketplace.vendor.suspended', $profile, ['reason' => $reason]);
            $profile->user->notify(new VendorAccountSuspended($profile, $reason));

            return $profile;
        });
    }

    public function reactivate(VendorProfile $profile, User $admin): VendorProfile
    {
        return DB::transaction(function () use ($profile, $admin) {
            $profile->forceFill([
                'status' => 'active',
                'suspended_at' => null,
                'suspension_reason' => null,
            ])->save();

            $this->audit->record($admin, 'marketplace.vendor.reactivated', $profile);
            $profile->user->notify(new VendorApplicationApproved($profile));

            return $profile;
        });
    }

    public function setCommissionOverride(VendorProfile $profile, User $admin, ?float $rate): VendorProfile
    {
        $old = $profile->commission_rate_override;
        $profile->forceFill(['commission_rate_override' => $rate])->save();

        $this->audit->record($admin, 'marketplace.vendor.commission_updated', $profile, [
            'from' => $old,
            'to' => $rate,
        ]);

        return $profile;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'vendeur';
        $slug = $base;
        $i = 2;

        while (VendorProfile::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
