<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\RouteStation;
use App\Models\Schedule;
use Illuminate\Support\Collection;

class ReservationAvailabilityEngine
{
    public function isReserved(int $scheduleId, int $seatId, mixed $travelDate, ?int $startStationId = null, ?int $leaveStationId = null): bool
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

        $reservations = $query->get();

        if ($startStationId === null || $leaveStationId === null) {
            return $reservations->isNotEmpty();
        }

        $requestedRange = $this->resolveJourneyRange($scheduleId, $startStationId, $leaveStationId);

        if ($requestedRange === null) {
            return $reservations->isNotEmpty();
        }

        return $reservations->contains(function (Reservation $reservation) use ($scheduleId, $requestedRange) {
            $existingRange = $this->resolveJourneyRange(
                $scheduleId,
                (int) $reservation->start_station_id,
                (int) $reservation->leave_station_id,
            );

            return $existingRange !== null && $this->rangesOverlap($requestedRange, $existingRange);
        });
    }

    public function reservedSeatIds(int $scheduleId, mixed $travelDate, ?array $seatIds = null, ?int $startStationId = null, ?int $leaveStationId = null): array
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

        $reservations = $query->get();

        if ($startStationId === null || $leaveStationId === null) {
            return $reservations->pluck('seat_id')->map(fn ($seatId) => (int) $seatId)->all();
        }

        $requestedRange = $this->resolveJourneyRange($scheduleId, $startStationId, $leaveStationId);

        if ($requestedRange === null) {
            return $reservations->pluck('seat_id')->map(fn ($seatId) => (int) $seatId)->all();
        }

        return $reservations
            ->filter(function (Reservation $reservation) use ($scheduleId, $requestedRange) {
                $existingRange = $this->resolveJourneyRange(
                    $scheduleId,
                    (int) $reservation->start_station_id,
                    (int) $reservation->leave_station_id,
                );

                return $existingRange !== null && $this->rangesOverlap($requestedRange, $existingRange);
            })
            ->pluck('seat_id')
            ->map(fn ($seatId) => (int) $seatId)
            ->all();
    }

    private function resolveJourneyRange(int $scheduleId, int $startStationId, int $leaveStationId): ?array
    {
        if ($startStationId === $leaveStationId) {
            return null;
        }

        $stations = $this->stationSequences($scheduleId, [$startStationId, $leaveStationId]);

        if ($stations->count() !== 2) {
            return null;
        }

        $start = $stations->get($startStationId);
        $leave = $stations->get($leaveStationId);

        if ($start === null || $leave === null || $start >= $leave) {
            return null;
        }

        return [$start, $leave];
    }

    private function stationSequences(int $scheduleId, array $stationIds): Collection
    {
        return RouteStation::query()
            ->where('route_id', Schedule::query()->whereKey($scheduleId)->value('route_id'))
            ->whereIn('station_id', $stationIds)
            ->pluck('sequence', 'station_id')
            ->map(fn ($sequence) => (int) $sequence);
    }

    private function rangesOverlap(array $leftRange, array $rightRange): bool
    {
        [$leftStart, $leftEnd] = $leftRange;
        [$rightStart, $rightEnd] = $rightRange;

        return max($leftStart, $rightStart) < min($leftEnd, $rightEnd);
    }
}
