<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\ServiceAvailabilityService;
use App\Services\ServiceBookingNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * §18-§19 — administration des rendez-vous : vue d'ensemble, déplacement
 * (avec notification de modification) et annulation (avec notification
 * d'annulation) d'une réservation.
 */
class ServiceBookingController extends Controller
{
    public function __construct(
        private readonly ServiceAvailabilityService $availability,
        private readonly ServiceBookingNotifier $notifier,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $productId = $request->query('product');
        $scope = $request->query('scope', 'upcoming');

        $bookings = OrderItem::query()
            ->where('product_type', 'service')
            ->whereNotNull('reservation_datetime')
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid'))
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->when($scope === 'upcoming', fn ($q) => $q->where('reservation_datetime', '>=', now()))
            ->when($scope === 'past', fn ($q) => $q->where('reservation_datetime', '<', now()))
            ->with(['order.user:id,first_name,last_name,email', 'product:id,name,duration_minutes,location,meeting_link'])
            ->orderBy('reservation_datetime', $scope === 'past' ? 'desc' : 'asc')
            ->paginate(30)
            ->withQueryString();

        $services = Product::query()->where('type', 'service')
            ->withCount('schedules')
            ->orderBy('name')
            ->get(['id', 'name', 'duration_minutes', 'status']);

        return \Inertia\Inertia::render('Admin/Calendar/Index', [
            'heading' => 'Calendrier',
            'eyebrow' => 'Services',
            'bookings' => $bookings,
            'services' => $services,
            'productId' => $productId,
            'scope' => $scope,
        ]);
    }

    public function reschedule(Request $request, OrderItem $orderItem): RedirectResponse
    {
        abort_unless($orderItem->product_type === 'service' && $orderItem->reservation_datetime, 404);

        $data = $request->validate([
            'reservation_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reservation_time' => ['required', 'date_format:H:i'],
        ]);

        $new = Carbon::parse("{$data['reservation_date']} {$data['reservation_time']}:00");
        $old = $orderItem->reservation_datetime->copy();

        if ($new->equalTo($old)) {
            return back()->with('success', 'Le rendez-vous est déjà sur ce créneau.');
        }

        if (! $this->availability->isSlotAvailable($orderItem->product, $new)) {
            return back()->withErrors(['reservation_time' => "Ce créneau n'est pas disponible pour ce service."]);
        }

        try {
            DB::transaction(function () use ($orderItem, $new) {
                $this->availability->releaseSlot($orderItem);
                $orderItem->reservation_datetime = $new;
                $orderItem->reminder_sent_at = null;
                $orderItem->save();
                $this->availability->reserveSlot($orderItem);
            });
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['reservation_time' => 'Impossible de déplacer le rendez-vous sur ce créneau.']);
        }

        $this->notifier->rescheduled($orderItem->fresh(['order.user', 'product']), $old);

        $this->audit->record(
            $request->user(),
            'booking.rescheduled',
            $orderItem->order,
            ['from' => $old->toIso8601String(), 'to' => $new->toIso8601String(), 'service' => $orderItem->product_name],
            "Rendez-vous « {$orderItem->product_name} » déplacé au ".$new->format('d/m/Y H:i'),
            $request,
        );

        return back()->with('success', 'Rendez-vous déplacé — le client a été notifié de la modification.');
    }

    public function cancel(Request $request, OrderItem $orderItem): RedirectResponse
    {
        abort_unless($orderItem->product_type === 'service' && $orderItem->reservation_datetime, 404);

        $this->availability->releaseSlot($orderItem);
        $this->notifier->cancelled($orderItem);

        $orderItem->update(['reservation_datetime' => null, 'reminder_sent_at' => now()]);

        $this->audit->record(
            $request->user(),
            'booking.cancelled',
            $orderItem->order,
            ['service' => $orderItem->product_name],
            "Rendez-vous « {$orderItem->product_name} » annulé",
            $request,
        );

        return back()->with('success', 'Rendez-vous annulé — le client a été notifié.');
    }
}
