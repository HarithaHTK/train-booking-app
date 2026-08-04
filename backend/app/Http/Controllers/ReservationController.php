<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Http\Requests\Reservation\UpdateReservationRequest;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\ScheduleStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $reservation = Reservation::create([
            'user_id' => $userId,
            'schedule_id' => $validated['schedule_id'],
            'start_station_id' => $validated['start_station_id'],
            'leave_station_id' => $validated['leave_station_id'],
            'seat_id' => $validated['seat_id'],
            'status' => $validated['status'] ?? 'pending',
            'checked_in_at' => $validated['checked_in_at'] ?? null,
            'checked_out_at' => $validated['checked_out_at'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return response()->json([
            'message' => 'Reservation created successfully.',
            'reservation' => $this->loadReservation($reservation),
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

        $reservation->fill($validated);
        $reservation->updated_by = $request->user()?->id;
        $reservation->save();

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

        return response()->json([
            'message' => 'Reservation deleted successfully.',
        ]);
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
