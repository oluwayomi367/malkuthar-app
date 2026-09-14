<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * §79 — administration des coupons de réduction.
 */
class CouponController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        $coupons = Coupon::query()->latest()->paginate(50);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $coupon = Coupon::create($data);

        $this->audit->record($request->user(), 'coupon.created', $coupon, $data, "Coupon {$coupon->code} créé", $request);

        return back()->with('success', "Coupon {$coupon->code} créé.");
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $data = $this->validated($request, $coupon);
        $coupon->update($data);

        $this->audit->record($request->user(), 'coupon.updated', $coupon, $data, "Coupon {$coupon->code} modifié", $request);

        return back()->with('success', "Coupon {$coupon->code} mis à jour.");
    }

    public function destroy(Request $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update(['is_active' => false]);

        $this->audit->record($request->user(), 'coupon.deactivated', $coupon, [], "Coupon {$coupon->code} désactivé", $request);

        return back()->with('success', "Coupon {$coupon->code} désactivé.");
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($coupon)],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'gt:0'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
