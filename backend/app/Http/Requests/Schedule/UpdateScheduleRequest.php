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
            'departure_time' => ['sometimes', 'nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
