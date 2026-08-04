<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'train_id' => ['required', 'integer', 'exists:trains,id'],
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'departure_time' => ['sometimes', 'nullable', 'date_format:H:i:s'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
