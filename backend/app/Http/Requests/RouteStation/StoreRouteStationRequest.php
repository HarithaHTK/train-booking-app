<?php

namespace App\Http\Requests\RouteStation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRouteStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $routeId = (int) $this->input('route_id');

        return [
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'station_id' => [
                'required',
                'integer',
                'exists:stations,id',
                Rule::unique('route_stations', 'station_id')->where(fn ($query) => $query->where('route_id', $routeId)),
            ],
            'sequence' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('route_stations', 'sequence')->where(fn ($query) => $query->where('route_id', $routeId)),
            ],
        ];
    }
}