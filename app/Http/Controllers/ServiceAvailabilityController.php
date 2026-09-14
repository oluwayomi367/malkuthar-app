<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ServiceAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * §17 — le client choisit une date puis une heure disponible parmi les
 * créneaux réellement libres (§18).
 */
class ServiceAvailabilityController extends Controller
{
    public function __construct(private readonly ServiceAvailabilityService $availability) {}

    public function slots(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->type === 'service', 404);

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        $slots = $this->availability->availableSlotsForDate($product, Carbon::parse($data['date']));

        return response()->json(['date' => $data['date'], 'slots' => $slots]);
    }
}
