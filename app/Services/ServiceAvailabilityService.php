<?php

namespace App\Services;

use App\Exceptions\ServiceBookingException;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ServiceSchedule;
use App\Models\ServiceSlotLock;
use App\Models\ServiceTimeOff;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ServiceAvailabilityService — calendrier des services (§17-§19).
 *
 * Disponibilité = fenêtres hebdomadaires (ServiceSchedule) MOINS les
 * pauses/jours indisponibles (ServiceTimeOff) MOINS les créneaux déjà à
 * pleine capacité (ServiceSlotLock, compteur verrouillé pour rester
 * correct en cas de réservations concurrentes — même principe que le
 * stock physique).
 */
class ServiceAvailabilityService
{
    /**
     * §17 — heures de début de créneau disponibles pour un service à une
     * date donnée, alignées sur duration_minutes.
     *
     * @return list<string> heures au format H:i
     */
    public function availableSlotsForDate(Product $service, Carbon $date): array
    {
        $date = $date->copy()->startOfDay();
        $duration = max(1, (int) ($service->duration_minutes ?? 60));

        if ($this->isFullDayOff($service, $date)) {
            return [];
        }

        $windows = ServiceSchedule::query()
            ->where('product_id', $service->id)
            ->where('weekday', $date->dayOfWeek)
            ->get();

        if ($windows->isEmpty()) {
            return [];
        }

        $breaks = $this->partialTimeOff($service, $date);
        $slots = [];

        foreach ($windows as $window) {
            $cursor = $date->copy()->setTimeFromTimeString($window->start_time);
            $end = $date->copy()->setTimeFromTimeString($window->end_time);

            while ($cursor->copy()->addMinutes($duration)->lte($end)) {
                $slotStart = $cursor->copy();
                $slotEnd = $slotStart->copy()->addMinutes($duration);

                $blocked = $breaks->contains(
                    fn (array $b) => $slotStart->lt($b['end']) && $slotEnd->gt($b['start'])
                );

                if (! $blocked && $this->remainingCapacity($service, $slotStart, (int) $window->capacity) > 0) {
                    $slots[] = $slotStart->format('H:i');
                }

                $cursor->addMinutes($duration);
            }
        }

        sort($slots);

        return array_values(array_unique($slots));
    }

    public function isSlotAvailable(Product $service, Carbon $slotDatetime): bool
    {
        return in_array(
            $slotDatetime->format('H:i'),
            $this->availableSlotsForDate($service, $slotDatetime->copy()->startOfDay()),
            true
        );
    }

    /**
     * Réserve le créneau d'une ligne de commande SERVICE, sous verrou
     * pessimiste (protection anti-survente d'un même créneau, §18).
     * Ignore silencieusement les lignes non-service ou sans réservation.
     *
     * @throws ServiceBookingException si le créneau est complet ou hors calendrier.
     */
    public function reserveSlot(OrderItem $item): void
    {
        if ($item->product_type !== 'service' || ! $item->reservation_datetime) {
            return;
        }

        DB::transaction(function () use ($item) {
            $service = Product::query()->find($item->product_id);
            if (! $service) {
                return;
            }

            $slot = $item->reservation_datetime->copy();
            $capacity = $this->capacityForSlot($service, $slot);

            if ($capacity <= 0) {
                throw ServiceBookingException::outsideSchedule($service->name, $slot->format('d/m/Y H:i'));
            }

            $lock = ServiceSlotLock::query()->firstOrCreate(
                ['product_id' => $service->id, 'slot_datetime' => $slot],
                ['booked_count' => 0]
            );

            $lock = ServiceSlotLock::query()->whereKey($lock->getKey())->lockForUpdate()->firstOrFail();

            if ($lock->booked_count + $item->quantity > $capacity) {
                throw ServiceBookingException::slotUnavailable($service->name, $slot->format('d/m/Y H:i'));
            }

            $lock->increment('booked_count', $item->quantity);
        });
    }

    /**
     * §80 — libère un créneau (annulation/remboursement).
     */
    public function releaseSlot(OrderItem $item): void
    {
        if ($item->product_type !== 'service' || ! $item->reservation_datetime) {
            return;
        }

        $lock = ServiceSlotLock::query()
            ->where('product_id', $item->product_id)
            ->where('slot_datetime', $item->reservation_datetime)
            ->lockForUpdate()
            ->first();

        $lock?->decrement('booked_count', min($item->quantity, $lock->booked_count));
    }

    /*
    |--------------------------------------------------------------------------
    | Administration du calendrier (§18)
    |--------------------------------------------------------------------------
    */

    /**
     * Remplace intégralement les créneaux hebdomadaires d'un service.
     *
     * @param  array<int, array{weekday: int, start_time: string, end_time: string, capacity?: int}>  $windows
     */
    public function setWeeklySchedule(Product $service, array $windows): void
    {
        DB::transaction(function () use ($service, $windows) {
            ServiceSchedule::query()->where('product_id', $service->id)->delete();

            foreach ($windows as $window) {
                ServiceSchedule::create([
                    'product_id' => $service->id,
                    'weekday' => $window['weekday'],
                    'start_time' => $window['start_time'],
                    'end_time' => $window['end_time'],
                    'capacity' => $window['capacity'] ?? 1,
                ]);
            }
        });
    }

    /**
     * @param  Product|null  $service  null = s'applique à tous les services
     */
    public function addTimeOff(?Product $service, string $date, ?string $startTime, ?string $endTime, ?string $reason = null): ServiceTimeOff
    {
        return ServiceTimeOff::create([
            'product_id' => $service?->id,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'reason' => $reason,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Internes
    |--------------------------------------------------------------------------
    */

    private function capacityForSlot(Product $service, Carbon $slot): int
    {
        if ($this->isFullDayOff($service, $slot)) {
            return 0;
        }

        $time = $slot->format('H:i:s');

        $window = ServiceSchedule::query()
            ->where('product_id', $service->id)
            ->where('weekday', $slot->dayOfWeek)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->first();

        if (! $window) {
            return 0;
        }

        foreach ($this->partialTimeOff($service, $slot) as $break) {
            if ($slot->gte($break['start']) && $slot->lt($break['end'])) {
                return 0;
            }
        }

        return (int) $window->capacity;
    }

    private function remainingCapacity(Product $service, Carbon $slotStart, int $capacity): int
    {
        $booked = (int) (ServiceSlotLock::query()
            ->where('product_id', $service->id)
            ->where('slot_datetime', $slotStart)
            ->value('booked_count') ?? 0);

        return max(0, $capacity - $booked);
    }

    private function isFullDayOff(Product $service, Carbon $date): bool
    {
        return ServiceTimeOff::query()
            ->where(fn ($q) => $q->where('product_id', $service->id)->orWhereNull('product_id'))
            ->whereDate('date', $date->toDateString())
            ->whereNull('start_time')
            ->whereNull('end_time')
            ->exists();
    }

    /**
     * @return Collection<int, array{start: Carbon, end: Carbon}>
     */
    private function partialTimeOff(Product $service, Carbon $date): Collection
    {
        return ServiceTimeOff::query()
            ->where(fn ($q) => $q->where('product_id', $service->id)->orWhereNull('product_id'))
            ->whereDate('date', $date->toDateString())
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->get()
            ->map(fn (ServiceTimeOff $t) => [
                'start' => $date->copy()->setTimeFromTimeString((string) $t->start_time),
                'end' => $date->copy()->setTimeFromTimeString((string) $t->end_time),
            ]);
    }
}
