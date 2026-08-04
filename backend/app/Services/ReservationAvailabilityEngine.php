<?php

namespace App\Services;

use App\Models\Reservation;

class ReservationAvailabilityEngine
{
    public function isReserved(int $scheduleId, int $seatId, mixed $travelDate): bool
    {
        $query = Reservation::query()
            ->where('schedule_id', $scheduleId)
            ->where('seat_id', $seatId)
            ->whereNull('deleted_at')
            ->whereIn('status', ['pending', 'confirmed']);

        if ($travelDate) {
            $query->whereDate('travel_date', $travelDate);
        } else {
            $query->whereNull('travel_date');
        }

        return $query->exists();
    }

    public function reservedSeatIds(int $scheduleId, mixed $travelDate, ?array $seatIds = null): array
    {
        $query = Reservation::query()
            ->where('schedule_id', $scheduleId)
            ->whereNull('deleted_at')
            ->whereIn('status', ['pending', 'confirmed']);

        if ($travelDate) {
            $query->whereDate('travel_date', $travelDate);
        } else {
            $query->whereNull('travel_date');
        }

        if (! empty($seatIds)) {
            $query->whereIn('seat_id', $seatIds);
        }

        return $query->pluck('seat_id')->map(fn ($seatId) => (int) $seatId)->all();
    }
}
