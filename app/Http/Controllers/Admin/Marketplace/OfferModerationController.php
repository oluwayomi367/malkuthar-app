<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceOffer;
use App\Services\Marketplace\OfferModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * MARKETPLACE — file de modération des offres.
 */
class OfferModerationController extends Controller
{
    public function __construct(private readonly OfferModerationService $moderation) {}

    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', 'pending_review');

        $offers = MarketplaceOffer::query()
            ->with('vendor:id,display_name', 'category:id,name')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
            ->when($request->query('vendor'), fn ($q, $v) => $q->whereHas('vendor',
                fn ($vq) => $vq->where('display_name', 'like', "%{$v}%")))
            ->orderBy('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Marketplace/Offers/Index', [
            'offers' => $offers,
            'status' => $status,
            'counts' => MarketplaceOffer::query()
                ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(MarketplaceOffer $offer): Response
    {
        $offer->load('vendor.user', 'category', $offer->type, 'reviews.reviewer', 'approver');

        $pendingRevision = $offer->revisions()->where('state', 'pending')->latest('version')->first();
        $liveRevision = $offer->revisions()->where('state', 'approved')->latest('version')->first();

        return Inertia::render('Admin/Marketplace/Offers/Show', [
            'offer' => $offer,
            'pendingRevision' => $pendingRevision,
            'liveRevision' => $liveRevision,
            'decision_heading' => 'Décision',
        ]);
    }

    public function approve(Request $request, MarketplaceOffer $offer): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $this->moderation->approve($offer, $request->user(), $data['note'] ?? null);

        return redirect()->route('admin.marketplace.offers.index')->with('success', "« {$offer->title} » approuvée.");
    }

    public function reject(Request $request, MarketplaceOffer $offer): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->moderation->reject($offer, $request->user(), $data['reason']);

        return redirect()->route('admin.marketplace.offers.index')->with('success', 'Offre refusée.');
    }

    public function requestChanges(Request $request, MarketplaceOffer $offer): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->moderation->requestChanges($offer, $request->user(), $data['note']);

        return redirect()->route('admin.marketplace.offers.index')->with('success', 'Corrections demandées.');
    }

    public function suspend(Request $request, MarketplaceOffer $offer): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->moderation->suspend($offer, $request->user(), $data['reason']);

        return back()->with('success', 'Offre suspendue.');
    }

    public function unsuspend(Request $request, MarketplaceOffer $offer): RedirectResponse
    {
        $this->moderation->unsuspend($offer, $request->user());

        return back()->with('success', 'Offre rétablie.');
    }

    public function commission(Request $request, MarketplaceOffer $offer): RedirectResponse
    {
        $data = $request->validate([
            'commission_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:90'],
        ]);

        $rate = ($data['commission_rate_percent'] === null || $data['commission_rate_percent'] === '')
            ? null
            : round((float) $data['commission_rate_percent'] / 100, 4);

        $old = $offer->commission_rate;
        $offer->forceFill(['commission_rate' => $rate])->save();

        app(\App\Services\AuditLogger::class)->record($request->user(), 'marketplace.offer.commission_updated', $offer, [
            'from' => $old,
            'to' => $rate,
        ]);

        return back()->with('success', 'Le taux de commission spécifique à ce produit a été mis à jour.');
    }
}
