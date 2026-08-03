<?php

namespace Database\Factories;

use App\Models\RouteStation;
use App\Models\Station;
use App\Models\TrainRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RouteStation>
 */
class RouteStationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<RouteStation>
     */
    protected $model = RouteStation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'route_id' => TrainRoute::factory(),
            'station_id' => Station::factory(),
            'sequence' => fake()->numberBetween(1, 20),
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }
}