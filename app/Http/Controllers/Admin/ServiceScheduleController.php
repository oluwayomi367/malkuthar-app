<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\ServiceAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * §18 — administration du calendrier d'un service : jours disponibles,
 * horaires, nombre de rendez-vous possibles en parallèle (la « capacité »
 * — l'équivalent du stock pour un produit non matériel).
 */
class ServiceScheduleController extends Controller
{
    public function __construct(
        private readonly ServiceAvailabilityService $availability,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * PUT /admin/services/{product}/schedule — remplace le calendrier
     * hebdomadaire complet du service.
     */
    public function update(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        abort_unless($product->type === 'service', 404, 'Seuls les services ont un calendrier.');

        $data = $request->validate([
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'windows' => ['nullable', 'array'],
            'windows.*.weekday' => ['required', 'integer', 'between:0,6'],
            'windows.*.start_time' => ['required', 'date_format:H:i'],
            'windows.*.end_time' => ['required', 'date_format:H:i', 'after:windows.*.start_time'],
            'windows.*.capacity' => ['nullable', 'integer', 'min:1'],
        ]);

        $windows = $data['windows'] ?? [];

        if (! empty($data['duration_minutes'])) {
            $product->update(['duration_minutes' => (int) $data['duration_minutes']]);
        }

        $this->availability->setWeeklySchedule($product, $windows);

        $this->audit->record(
            $request->user(),
            'service.schedule_updated',
            $product,
            ['windows' => count($windows)],
            "Calendrier de « {$product->name} » mis à jour (".count($windows).' créneau·x)',
            $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Calendrier mis à jour.']);
        }

        return back()->with('success', 'Calendrier du service mis à jour.');
    }
}
