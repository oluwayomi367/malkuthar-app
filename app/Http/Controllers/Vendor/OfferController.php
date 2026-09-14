<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\OfferRequest;
use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceOffer;
use App\Services\Marketplace\CategoryFieldSchemaResolver;
use App\Services\Marketplace\OfferMediaService;
use App\Services\Marketplace\OfferModerationService;
use App\Services\Marketplace\OfferSubmissionService;
use App\Services\Marketplace\OfferWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MARKETPLACE — le vendeur gère ses offres.
 */
class OfferController extends Controller
{
    public function __construct(
        private readonly OfferWriter $writer,
        private readonly OfferSubmissionService $submission,
        private readonly OfferModerationService $moderation,
        private readonly OfferMediaService $media,
    ) {}

    /**
     * Ajoute au jeu de données les chemins d'images (principale, galerie,
     * variantes) à partir des fichiers téléversés.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withMedia(OfferRequest $request, ?MarketplaceOffer $offer, array $data): array
    {
        $data = $this->media->apply($offer, $data, [
            'main_image' => $request->file('main_image'),
            'gallery' => (array) $request->file('gallery', []),
            'gallery_remove' => (array) $request->input('gallery_remove', []),
            'remove_main_image' => $request->boolean('remove_main_image'),
        ]);

        foreach ($data['variants'] ?? [] as $i => $variant) {
            if ($request->hasFile("variants.{$i}.image")) {
                $data['variants'][$i]['image_path'] = $this->media->storeVariantImage($request->file("variants.{$i}.image"));
            }
        }

        return $data;
    }

    public function index(Request $request): View
    {
        $vendor = $request->attributes->get('vendorProfile');

        $offers = MarketplaceOffer::query()
            ->where('vendor_profile_id', $vendor->id)
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('vendor.offers.index', compact('offers'));
    }

    public function create(Request $request): View
    {
        $type = $request->query('type');

        if (! in_array($type, MarketplaceOffer::TYPES, true)) {
            return view('vendor.offers.type-picker');
        }

        return view('vendor.offers.form', [
            'offer' => null,
            'presetType' => $type,
            'categories' => $this->activeCategories(),
            'schema' => collect(),
        ]);
    }

    public function store(OfferRequest $request): RedirectResponse
    {
        $vendor = $request->attributes->get('vendorProfile');
        $offer = $this->writer->create($vendor, $this->withMedia($request, null, $request->forWriter()));

        return redirect()
            ->route('vendor.offers.edit', $offer)
            ->with('success', 'Brouillon créé. Complétez la fiche puis soumettez-la à validation.');
    }

    public function edit(Request $request, MarketplaceOffer $offer): View
    {
        $this->authorizeOffer($request, $offer);
        $offer->load('category', $offer->type, 'revisions', 'variants');

        return view('vendor.offers.form', [
            'offer' => $offer,
            'presetType' => $offer->type,
            'categories' => $this->activeCategories(),
            'schema' => $offer->category
                ? app(CategoryFieldSchemaResolver::class)->schemaFor($offer->category)
                : collect(),
        ]);
    }

    public function update(OfferRequest $request, MarketplaceOffer $offer): RedirectResponse
    {
        $this->authorizeOffer($request, $offer);

        $this->submission->applyVendorEdit(
            $offer,
            $this->withMedia($request, $offer, $request->forWriter()),
            $request->user(),
        );

        return back()->with('success', $this->submission->isLive($offer)
            ? 'Modifications enregistrées. Soumettez-les pour qu\'elles remplacent la version en ligne.'
            : 'Offre enregistrée.');
    }

    public function submit(Request $request, MarketplaceOffer $offer): RedirectResponse
    {
        $this->authorizeOffer($request, $offer);
        $this->submission->submit($offer, $request->user());

        return back()->with('success', 'Offre soumise. Vous serez notifié·e après la revue.');
    }

    public function withdraw(Request $request, MarketplaceOffer $offer): RedirectResponse
    {
        $this->authorizeOffer($request, $offer);
        $this->submission->withdraw($offer, $request->user());

        return back()->with('success', 'Soumission retirée.');
    }

    public function publish(Request $request, MarketplaceOffer $offer): RedirectResponse
    {
        $this->authorizeOffer($request, $offer);
        $this->moderation->setOnline($offer, $request->user(), true);

        return back()->with('success', 'Offre remise en ligne.');
    }

    public function unpublish(Request $request, MarketplaceOffer $offer): RedirectResponse
    {
        $this->authorizeOffer($request, $offer);
        $this->moderation->setOnline($offer, $request->user(), false);

        return back()->with('success', 'Offre retirée de la vente.');
    }

    public function destroy(Request $request, MarketplaceOffer $offer): RedirectResponse
    {
        $this->authorizeOffer($request, $offer);
        abort_if($offer->status === 'pending_review', 422, 'Retirez d\'abord la soumission.');

        $offer->delete();

        return redirect()->route('vendor.offers.index')->with('success', 'Offre supprimée.');
    }

    private function authorizeOffer(Request $request, MarketplaceOffer $offer): void
    {
        abort_unless($offer->vendor_profile_id === $request->attributes->get('vendorProfile')->id, 403);
    }

    private function activeCategories()
    {
        return MarketplaceCategory::query()->active()->orderBy('path')->get();
    }
}
