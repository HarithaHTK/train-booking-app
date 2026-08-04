<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Http\Requests\Reservation\UpdateReservationRequest;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\ScheduleStation;
use App\Models\Seat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class ReservationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/reservations",
     *     tags={"Reservations"},
     *     summary="List reservations",
     *     operationId="listReservations",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Reservations retrieved successfully")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Reservation::query()->with(['user', 'schedule.train', 'startStation', 'leaveStation', 'seat']);

        if (! $request->user()?->hasRole('admin')) {
            $query->where('user_id', $request->user()?->id);
        }

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('schedule_id')) {
            $query->where('schedule_id', $request->integer('schedule_id'));
        }

        if ($request->filled('start_station_id')) {
            $query->where('start_station_id', $request->integer('start_station_id'));
        }

        if ($request->filled('leave_station_id')) {
            $query->where('leave_station_id', $request->integer('leave_station_id'));
        }

        if ($request->filled('seat_id')) {
            $query->where('seat_id', $request->integer('seat_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'reservations' => $query->latest()->get(),
        ]);
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()?->id;

        $reservations = DB::transaction(function () use ($validated, $userId) {
            $seatIds = array_values(array_unique($validated['seat_ids'] ?? [$validated['seat_id']]));

            $seats = Seat::query()
                ->whereIn('id', $seatIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            abort_if($seats->count() !== count($seatIds), 422, 'One or more selected seats could not be found.');

            foreach ($seats as $seat) {
                abort_if($seat->is_reserved, 422, 'One of the selected seats is already reserved.');
            }

            $reservations = collect();

            foreach ($seatIds as $seatId) {
                $reservation = Reservation::create([
                    'user_id' => $userId,
                    'schedule_id' => $validated['schedule_id'],
                    'start_station_id' => $validated['start_station_id'],
                    'leave_station_id' => $validated['leave_station_id'],
                    'seat_id' => $seatId,
                    'status' => $validated['status'] ?? 'pending',
                    'checked_in_at' => $validated['checked_in_at'] ?? null,
                    'checked_out_at' => $validated['checked_out_at'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                $seat = $seats->get($seatId);
                $seat->is_reserved = true;
                $seat->updated_by = $userId;
                $seat->save();

                $reservations->push($reservation);
            }

            return $reservations;
        });

        return response()->json([
            'message' => 'Reservation created successfully.',
            'reservations' => $reservations->map(fn (Reservation $reservation) => $this->loadReservation($reservation)),
            'reservation' => $this->loadReservation($reservations->first()),
        ], 201);
    }

    public function show(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorizeReservation($request, $reservation);

        return response()->json([
            'reservation' => $this->loadReservation($reservation),
        ]);
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation): JsonResponse
    {
        $this->authorizeReservation($request, $reservation);

        $validated = $request->validated();
        $previousStatus = $reservation->status;

        $reservation->fill($validated);
        $reservation->updated_by = $request->user()?->id;
        $reservation->save();

        if (($validated['status'] ?? null) === 'cancelled' && $previousStatus !== 'cancelled') {
            $this->syncSeatReservationState($reservation->seat_id, $request->user()?->id);
        }

        return response()->json([
            'message' => 'Reservation updated successfully.',
            'reservation' => $this->loadReservation($reservation->fresh()),
        ]);
    }

    public function destroy(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorizeReservation($request, $reservation);

        $reservation->deleted_by = $request->user()?->id;
        $reservation->save();
        $reservation->delete();

        $this->syncSeatReservationState($reservation->seat_id, $request->user()?->id);

        return response()->json([
            'message' => 'Reservation deleted successfully.',
        ]);
    }

    private function syncSeatReservationState(int $seatId, ?int $userId = null): void
    {
        $seat = Seat::query()->whereKey($seatId)->first();

        if (! $seat) {
            return;
        }

        $isReserved = Reservation::query()
            ->where('seat_id', $seatId)
            ->whereNull('deleted_at')
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        $seat->is_reserved = $isReserved;
        $seat->updated_by = $userId;
        $seat->save();
    }

    private function loadReservation(Reservation $reservation): Reservation
    {
        return $reservation->load(['user', 'schedule.train', 'startStation', 'leaveStation', 'seat']);
    }

    private function authorizeReservation(Request $request, Reservation $reservation): void
    {
        if ($request->user()?->hasRole('admin')) {
            return;
        }

        abort_unless($reservation->user_id === $request->user()?->id, 403);
    }
}
