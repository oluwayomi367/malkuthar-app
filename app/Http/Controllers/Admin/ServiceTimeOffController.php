<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ServiceTimeOff;
use App\Services\AuditLogger;
use App\Services\ServiceAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * §18 — pauses, jours indisponibles, congés d'un service. `product_id`
 * absent = s'applique à tous les services (fermeture générale MALKUTHAR).
 */
class ServiceTimeOffController extends Controller
{
    public function __construct(
        private readonly ServiceAvailabilityService $availability,
        private readonly AuditLogger $audit,
    ) {}

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_with:start_time', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $product = isset($data['product_id']) ? Product::query()->find($data['product_id']) : null;

        $timeOff = $this->availability->addTimeOff(
            $product,
            $data['date'],
            $data['start_time'] ?? null,
            $data['end_time'] ?? null,
            $data['reason'] ?? null,
        );

        $this->audit->record(
            $request->user(),
            'service.time_off_added',
            $product,
            $data,
            'Indisponibilité ajoutée le '.$data['date'].($product ? " pour « {$product->name} »" : ' (tous les services)'),
            $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Indisponibilité enregistrée.', 'data' => $timeOff], 201);
        }

        return back()->with('success', 'Indisponibilité enregistrée.');
    }

    public function destroy(Request $request, ServiceTimeOff $timeOff): JsonResponse|RedirectResponse
    {
        $product = $timeOff->product;
        $date = $timeOff->date?->toDateString();

        $timeOff->delete();

        $this->audit->record(
            $request->user(),
            'service.time_off_removed',
            $product,
            ['date' => $date],
            'Indisponibilité du '.$date.' supprimée',
            $request,
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Indisponibilité supprimée.']);
        }

        return back()->with('success', 'Indisponibilité supprimée.');
    }
}
