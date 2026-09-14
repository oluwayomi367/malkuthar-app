<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\MarketplaceOfferRevision;
use App\Models\User;
use App\Notifications\Marketplace\OfferSubmitted;
use App\Services\AuditLogger;
use App\Support\MarketplaceSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * MARKETPLACE — soumission d'une offre à la validation.
 *
 * - Offre jamais publiée (first_published_at = null) : l'édition mute la
 *   ligne cœur ; la soumission en fige un instantané dans une révision.
 * - Offre déjà en ligne : l'édition est mise en attente dans une révision
 *   `pending` (la ligne cœur reste la version publiée) ; la soumission ne
 *   fait que passer le statut en `pending_review`.
 */
class OfferSubmissionService
{
    public function __construct(
        private readonly OfferWriter $writer,
        private readonly AuditLogger $audit,
    ) {}

    public function isLive(MarketplaceOffer $offer): bool
    {
        return $offer->first_published_at !== null;
    }

    /**
     * Édition par le vendeur. Route vers mutation directe ou mise en attente
     * selon que l'offre a déjà été publiée.
     *
     * @param  array<string, mixed>  $data
     */
    public function applyVendorEdit(MarketplaceOffer $offer, array $data, User $user): MarketplaceOffer
    {
        if ($offer->status === 'pending_review') {
            throw ValidationException::withMessages([
                'offer' => 'Cette offre est en cours de revue. Retirez la soumission pour la modifier.',
            ]);
        }

        if ($this->isLive($offer)) {
            $this->stagePendingRevision($offer, array_merge($this->writer->snapshot($offer), $data), $user);

            return $offer;
        }

        $this->writer->applyData($offer, $data);

        if (in_array($offer->status, ['changes_requested', 'rejected'], true)) {
            $offer->forceFill(['status' => 'draft'])->save();
        }

        return $offer->refresh();
    }

    public function submit(MarketplaceOffer $offer, User $user): MarketplaceOffer
    {
        return DB::transaction(function () use ($offer, $user) {
            if (! $offer->canBeSubmitted() && ! $this->hasPendingRevision($offer)) {
                throw ValidationException::withMessages([
                    'offer' => "Cette offre ne peut pas être soumise dans son état actuel ({$offer->status}).",
                ]);
            }

            // Offre jamais publiée : on fige la ligne cœur en révision.
            if (! $this->isLive($offer) && ! $this->hasPendingRevision($offer)) {
                $this->createRevision($offer, $this->writer->snapshot($offer), $user);
            }

            $offer->forceFill([
                'status' => 'pending_review',
                'submitted_at' => now(),
                'review_note' => null,
                'rejection_reason' => null,
            ])->save();

            $this->audit->record($user, 'marketplace.offer.submitted', $offer, ['version' => $offer->version]);

            // Auto-approbation des vendeurs de confiance (option).
            if (MarketplaceSettings::autoApproveTrustedVendors() && $offer->vendor->is_trusted) {
                app(OfferModerationService::class)->approve($offer, $user, 'Auto-approuvé (vendeur de confiance).');

                return $offer->refresh();
            }

            $this->notifyReviewers($offer);

            return $offer->refresh();
        });
    }

    public function withdraw(MarketplaceOffer $offer, User $user): MarketplaceOffer
    {
        if ($offer->status !== 'pending_review') {
            throw ValidationException::withMessages(['offer' => 'Rien à retirer.']);
        }

        $offer->revisions()->where('state', 'pending')->update(['state' => 'superseded']);

        $offer->forceFill([
            'status' => $this->isLive($offer) ? 'approved' : 'draft',
            'submitted_at' => null,
        ])->save();

        $this->audit->record($user, 'marketplace.offer.withdrawn', $offer);

        return $offer->refresh();
    }

    /* -------------------------------------------------------------------- */

    private function stagePendingRevision(MarketplaceOffer $offer, array $payload, User $user): void
    {
        $offer->revisions()->where('state', 'pending')->update(['state' => 'superseded']);
        $this->createRevision($offer, $payload, $user);

        $this->audit->record($user, 'marketplace.offer.revision_staged', $offer);
    }

    private function createRevision(MarketplaceOffer $offer, array $payload, User $user): MarketplaceOfferRevision
    {
        $offer->forceFill(['version' => $offer->version + 1])->save();

        return $offer->revisions()->create([
            'version' => $offer->version,
            'payload' => $payload,
            'submitted_by' => $user->getKey(),
            'submitted_at' => now(),
            'state' => 'pending',
        ]);
    }

    private function hasPendingRevision(MarketplaceOffer $offer): bool
    {
        return $offer->revisions()->where('state', 'pending')->exists();
    }

    private function notifyReviewers(MarketplaceOffer $offer): void
    {
        User::query()->where('role', 'admin')->get()
            ->each(fn (User $admin) => $admin->notify(new OfferSubmitted($offer)));
    }
}
