<?php

namespace App\Http\Controllers\Admin\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceOffer;
use App\Services\Marketplace\OfferModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MARKETPLACE — file de modération des offres.
 */
class OfferModerationController extends Controller
{
    public function __construct(private readonly OfferModerationService $moderation) {}

    public function index(Request $request): View
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

        return view('admin.marketplace.offers.index', [
            'offers' => $offers,
            'status' => $status,
            'counts' => MarketplaceOffer::query()
                ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(MarketplaceOffer $offer): View
    {
        $offer->load('vendor.user', 'category', $offer->type, 'reviews.reviewer', 'approver');

        $pendingRevision = $offer->revisions()->where('state', 'pending')->latest('version')->first();
        $liveRevision = $offer->revisions()->where('state', 'approved')->latest('version')->first();

        return view('admin.marketplace.offers.show', compact('offer', 'pendingRevision', 'liveRevision'));
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
}
