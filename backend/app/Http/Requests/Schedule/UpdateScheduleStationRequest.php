<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduleStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $scheduleStation = $this->route('schedule_station');
        $scheduleId = (int) $this->input('schedule_id', $scheduleStation?->schedule_id);

        return [
            'schedule_id' => ['sometimes', 'required', 'integer', 'exists:schedules,id'],
            'station_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:stations,id',
                Rule::unique('schedule_stations', 'station_id')
                    ->where(fn ($query) => $query->where('schedule_id', $scheduleId))
                    ->ignore($scheduleStation?->id),
            ],
            'sequence' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                Rule::unique('schedule_stations', 'sequence')
                    ->where(fn ($query) => $query->where('schedule_id', $scheduleId))
                    ->ignore($scheduleStation?->id),
            ],
            'arrival_time' => ['sometimes', 'nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/'],
            'departure_time' => ['sometimes', 'nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/'],
        ];
    }
}
