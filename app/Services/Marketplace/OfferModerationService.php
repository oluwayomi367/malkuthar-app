<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\MarketplaceOffer;
use App\Models\User;
use App\Notifications\Marketplace\OfferModerated;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * MARKETPLACE — décisions de modération sur une offre.
 *
 * À l'approbation, le contenu de la dernière révision `pending` est recopié
 * sur la ligne cœur (OfferWriter::applyPayload) : la boutique publique lit
 * toujours la ligne cœur, qui ne contient donc que du contenu approuvé.
 */
class OfferModerationService
{
    public function __construct(
        private readonly OfferWriter $writer,
        private readonly AuditLogger $audit,
    ) {}

    public function approve(MarketplaceOffer $offer, User $admin, ?string $note = null): MarketplaceOffer
    {
        return DB::transaction(function () use ($offer, $admin, $note) {
            $this->assertReviewable($offer);

            $revision = $offer->revisions()->where('state', 'pending')->latest('version')->first();

            if ($revision) {
                $this->writer->applyPayload($offer, $revision->payload);
                $offer->revisions()->where('state', 'approved')->update(['state' => 'superseded']);
                $revision->update(['state' => 'approved']);
            }

            $firstPublish = $offer->first_published_at === null;

            $offer->forceFill([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $admin->getKey(),
                'first_published_at' => $offer->first_published_at ?? now(),
                'review_note' => $note,
                'rejection_reason' => null,
                // Première publication : en ligne d'office. Re-validation :
                // on ne touche pas au drapeau de visibilité choisi par le vendeur.
                'is_online' => $firstPublish ? true : $offer->is_online,
            ])->save();

            $this->recordReview($offer, $admin, 'approved', $note);
            $offer->vendor->user->notify(new OfferModerated($offer, 'approved', $note));
            $this->audit->record($admin, 'marketplace.offer.approved', $offer, ['first_publish' => $firstPublish]);

            return $offer->refresh();
        });
    }

    public function reject(MarketplaceOffer $offer, User $admin, string $reason): MarketplaceOffer
    {
        return DB::transaction(function () use ($offer, $admin, $reason) {
            $this->assertReviewable($offer);

            $offer->revisions()->where('state', 'pending')->update(['state' => 'rejected']);

            // Offre déjà en ligne : la version publiée reste, on note le refus.
            $live = $offer->first_published_at !== null;

            $offer->forceFill([
                'status' => $live ? 'approved' : 'rejected',
                'rejection_reason' => $live ? null : $reason,
                'review_note' => $live ? $reason : null,
            ])->save();

            $this->recordReview($offer, $admin, 'rejected', $reason);
            $offer->vendor->user->notify(new OfferModerated($offer, 'rejected', $reason));
            $this->audit->record($admin, 'marketplace.offer.rejected', $offer, ['reason' => $reason]);

            return $offer->refresh();
        });
    }

    public function requestChanges(MarketplaceOffer $offer, User $admin, string $note): MarketplaceOffer
    {
        return DB::transaction(function () use ($offer, $admin, $note) {
            $this->assertReviewable($offer);

            $offer->revisions()->where('state', 'pending')->update(['state' => 'superseded']);

            $offer->forceFill([
                'status' => 'changes_requested',
                'review_note' => $note,
            ])->save();

            $this->recordReview($offer, $admin, 'changes_requested', $note);
            $offer->vendor->user->notify(new OfferModerated($offer, 'changes_requested', $note));
            $this->audit->record($admin, 'marketplace.offer.changes_requested', $offer, ['note' => $note]);

            return $offer->refresh();
        });
    }

    public function suspend(MarketplaceOffer $offer, User $admin, string $reason): MarketplaceOffer
    {
        $offer->forceFill([
            'status' => 'suspended',
            'is_online' => false,
            'review_note' => $reason,
        ])->save();

        $this->recordReview($offer, $admin, 'rejected', 'Suspension : '.$reason);
        $offer->vendor->user->notify(new OfferModerated($offer, 'suspended', $reason));
        $this->audit->record($admin, 'marketplace.offer.suspended', $offer, ['reason' => $reason]);

        return $offer->refresh();
    }

    public function unsuspend(MarketplaceOffer $offer, User $admin): MarketplaceOffer
    {
        abort_unless($offer->status === 'suspended', 422);

        $offer->forceFill([
            'status' => 'approved',
            'is_online' => true,
        ])->save();

        $this->audit->record($admin, 'marketplace.offer.unsuspended', $offer);

        return $offer->refresh();
    }

    /** Bascule de visibilité par le vendeur (offre approuvée uniquement). */
    public function setOnline(MarketplaceOffer $offer, User $vendorUser, bool $online): MarketplaceOffer
    {
        abort_unless($offer->status === 'approved', 422, 'Seule une offre approuvée peut être mise en ligne.');

        $offer->forceFill(['is_online' => $online])->save();
        $this->audit->record($vendorUser, $online ? 'marketplace.offer.published' : 'marketplace.offer.unpublished', $offer);

        return $offer->refresh();
    }

    /* -------------------------------------------------------------------- */

    private function assertReviewable(MarketplaceOffer $offer): void
    {
        if (! in_array($offer->status, ['pending_review'], true)) {
            throw ValidationException::withMessages([
                'offer' => "Cette offre n'est pas en attente de revue (statut : {$offer->status}).",
            ]);
        }
    }

    private function recordReview(MarketplaceOffer $offer, User $admin, string $decision, ?string $note): void
    {
        $offer->reviews()->create([
            'offer_version' => $offer->version,
            'reviewer_id' => $admin->getKey(),
            'decision' => $decision,
            'note' => $note,
        ]);
    }
}
