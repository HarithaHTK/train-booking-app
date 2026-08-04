<?php

namespace App\Http\Requests\Schedule;

use App\Models\Train;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'train_id' => ['sometimes', 'required', 'integer', 'exists:trains,id'],
            'route_id' => ['sometimes', 'required', 'integer', 'exists:routes,id'],
            'departure_time' => ['sometimes', 'nullable', 'date_format:H:i:s'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
