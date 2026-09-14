<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplacePayoutAccount;
use App\Services\Marketplace\VendorPayoutService;
use App\Services\Marketplace\VendorSettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * MARKETPLACE — « Mes versements » : demandes de retrait + moyens de versement.
 */
class PayoutController extends Controller
{
    public function __construct(
        private readonly VendorPayoutService $payouts,
        private readonly VendorSettlementService $settlement,
    ) {}

    public function index(Request $request): View
    {
        $vendor = $request->attributes->get('vendorProfile');

        return view('vendor.payouts.index', [
            'balance' => $this->settlement->recompute($vendor),
            'payouts' => $vendor->payouts()->latest()->paginate(15),
            'accounts' => $vendor->payoutAccounts()->orderByDesc('is_default')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $vendor = $request->attributes->get('vendorProfile');

        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0'],
            'payout_account_id' => ['nullable', 'exists:marketplace_payout_accounts,id'],
        ]);

        try {
            $this->payouts->request(
                $vendor,
                $data['amount'] ?? null,
                $data['payout_account_id'] ?? null,
                $request->user(),
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Demande de versement enregistrée.');
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $vendor = $request->attributes->get('vendorProfile');

        $data = $request->validate([
            'method' => ['required', 'in:mobile_money,bank_transfer,manual'],
            'label' => ['required', 'string', 'max:120'],
            'number' => ['required', 'string', 'max:64'],
        ]);

        $isFirst = $vendor->payoutAccounts()->count() === 0;

        $vendor->payoutAccounts()->create([
            'method' => $data['method'],
            'label' => $data['label'],
            'details' => ['number' => $data['number']],
            'is_default' => $isFirst,
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Moyen de versement ajouté.');
    }

    public function makeDefaultAccount(Request $request, MarketplacePayoutAccount $account): RedirectResponse
    {
        $this->authorizeAccount($request, $account);

        $account->vendorProfile->payoutAccounts()->update(['is_default' => false]);
        $account->update(['is_default' => true, 'changed_at' => now()]);

        return back()->with('success', 'Moyen de versement par défaut mis à jour.');
    }

    public function destroyAccount(Request $request, MarketplacePayoutAccount $account): RedirectResponse
    {
        $this->authorizeAccount($request, $account);
        $account->delete();

        return back()->with('success', 'Moyen de versement supprimé.');
    }

    private function authorizeAccount(Request $request, MarketplacePayoutAccount $account): void
    {
        abort_unless(
            $account->vendor_profile_id === $request->attributes->get('vendorProfile')->id,
            403,
        );
    }
}
