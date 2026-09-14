<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\VendorLedgerEntry;
use App\Services\Marketplace\VendorSettlementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MARKETPLACE — « Mes revenus » du vendeur : solde + registre.
 */
class SettlementController extends Controller
{
    public function __construct(private readonly VendorSettlementService $settlement) {}

    public function index(Request $request): View
    {
        $vendor = $request->attributes->get('vendorProfile');
        $balance = $this->settlement->recompute($vendor);

        $entries = VendorLedgerEntry::query()
            ->where('vendor_profile_id', $vendor->id)
            ->when($request->query('type'), fn ($q, $t) => $q->where('source_type', $t))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('vendor.settlement.index', compact('balance', 'entries'));
    }
}
