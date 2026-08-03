<?php

namespace Database\Factories;

use App\Models\TrainRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainRoute>
 */
class TrainRouteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TrainRoute>
     */
    protected $model = TrainRoute::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true).' Line',
            'description' => fake()->sentence(),
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }
}