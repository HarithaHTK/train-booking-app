<?php

namespace App\Http\Requests\Reservation;

use App\Models\Schedule;
use App\Models\ScheduleStation;
use App\Models\Reservation;
use App\Models\Seat;
use App\Services\ReservationAvailabilityEngine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'start_station_id' => [
                'required',
                'integer',
                'exists:stations,id',
                function ($attribute, $value, $fail) {
                    $scheduleId = (int) $this->input('schedule_id');
                    $schedule = Schedule::find($scheduleId);

                    if (! $schedule) {
                        return;
                    }

                    $exists = ScheduleStation::query()
                        ->where('schedule_id', $scheduleId)
                        ->where('station_id', (int) $value)
                        ->exists();

                    if (! $exists) {
                        $fail('The selected start station must belong to the selected schedule.');
                    }
                },
            ],
            'leave_station_id' => [
                'required',
                'integer',
                'exists:stations,id',
                function ($attribute, $value, $fail) {
                    $scheduleId = (int) $this->input('schedule_id');
                    $schedule = Schedule::find($scheduleId);

                    if (! $schedule) {
                        return;
                    }

                    $exists = ScheduleStation::query()
                        ->where('schedule_id', $scheduleId)
                        ->where('station_id', (int) $value)
                        ->exists();

                    if (! $exists) {
                        $fail('The selected leave station must belong to the selected schedule.');
                    }
                },
            ],
            'seat_id' => [
                'sometimes',
                'required_without:seat_ids',
                'integer',
                'exists:seats,id',
                function ($attribute, $value, $fail) {
                    $seatId = (int) $value;
                    $seat = Seat::query()->find($seatId);
                    $scheduleId = (int) $this->input('schedule_id');
                    $travelDate = $this->input('travel_date');

                    if ($seat && $this->isSeatAlreadyReservedForJourney($seatId, $scheduleId, $travelDate)) {
                        $fail('The selected seat is already reserved.');
                    }
                },
            ],
            'seat_ids' => ['sometimes', 'required_without:seat_id', 'array', 'min:1'],
            'seat_ids.*' => [
                'integer',
                'exists:seats,id',
                function ($attribute, $value, $fail) {
                    $seatId = (int) $value;
                    $seat = Seat::query()->find($seatId);
                    $scheduleId = (int) $this->input('schedule_id');
                    $travelDate = $this->input('travel_date');

                    if ($seat && $this->isSeatAlreadyReservedForJourney($seatId, $scheduleId, $travelDate)) {
                        $fail('One of the selected seats is already reserved.');
                    }
                },
            ],
            'travel_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'string', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'checked_in_at' => ['sometimes', 'nullable', 'date'],
            'checked_out_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $scheduleId = (int) $this->input('schedule_id');
            $startStationId = (int) $this->input('start_station_id');
            $leaveStationId = (int) $this->input('leave_station_id');

            if (! $scheduleId || ! $startStationId || ! $leaveStationId) {
                return;
            }

            $stations = ScheduleStation::query()
                ->where('schedule_id', $scheduleId)
                ->whereIn('station_id', [$startStationId, $leaveStationId])
                ->get()
                ->keyBy('station_id');

            $start = $stations->get($startStationId);
            $leave = $stations->get($leaveStationId);

            if ($start && $leave && $start->sequence >= $leave->sequence) {
                $validator->errors()->add('leave_station_id', 'The leave station must come after the start station.');
            }
        });
    }

    private function isSeatAlreadyReservedForJourney(int $seatId, int $scheduleId, mixed $travelDate): bool
    {
        return app(ReservationAvailabilityEngine::class)->isReserved($scheduleId, $seatId, $travelDate);
    }
}
