<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\VendorProfile;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * MARKETPLACE — le vendeur édite sa boutique (identité publique).
 */
class VendorProfileController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function edit(Request $request): View
    {
        return view('vendor.profile', [
            'profile' => $request->attributes->get('vendorProfile'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var VendorProfile $profile */
        $profile = $request->attributes->get('vendorProfile');

        $data = $request->validate([
            'display_name' => ['required', 'string', 'min:2', 'max:120'],
            'bio' => ['required', 'string', 'min:30', 'max:2000'],
            'region' => ['required', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'banner' => ['nullable', 'image', 'max:4096'],
        ]);

        foreach (['logo' => 'logo_path', 'banner' => 'banner_path'] as $input => $column) {
            if ($request->hasFile($input)) {
                $path = $request->file($input)->store('marketplace/vendors', 'public');
                if ($profile->{$column}) {
                    Storage::disk('public')->delete($profile->{$column});
                }
                $profile->{$column} = $path;
            }
        }

        $profile->fill([
            'display_name' => $data['display_name'],
            'bio' => $data['bio'],
            'region' => $data['region'],
            'city' => $data['city'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ])->save();

        $this->audit->record($request->user(), 'marketplace.vendor.profile_updated', $profile);

        return back()->with('success', 'Votre boutique a été mise à jour.');
    }
}
