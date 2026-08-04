<?php

namespace App\Http\Requests\Reservation;

use App\Models\Schedule;
use App\Models\ScheduleStation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'schedule_id' => ['sometimes', 'required', 'integer', 'exists:schedules,id'],
            'start_station_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:stations,id',
                function ($attribute, $value, $fail) {
                    $scheduleId = (int) $this->input('schedule_id', $this->route('reservation')?->schedule_id);

                    if (! $scheduleId) {
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
                'sometimes',
                'required',
                'integer',
                'exists:stations,id',
                function ($attribute, $value, $fail) {
                    $scheduleId = (int) $this->input('schedule_id', $this->route('reservation')?->schedule_id);

                    if (! $scheduleId) {
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
            'seat_id' => ['sometimes', 'required', 'integer', 'exists:seats,id'],
            'status' => ['sometimes', 'string', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'checked_in_at' => ['sometimes', 'nullable', 'date'],
            'checked_out_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $reservation = $this->route('reservation');
            $scheduleId = (int) $this->input('schedule_id', $reservation?->schedule_id);
            $startStationId = (int) $this->input('start_station_id', $reservation?->start_station_id);
            $leaveStationId = (int) $this->input('leave_station_id', $reservation?->leave_station_id);

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
}
