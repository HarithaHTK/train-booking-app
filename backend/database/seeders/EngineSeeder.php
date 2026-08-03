<?php

namespace Database\Seeders;

use App\Models\Engine;
use Illuminate\Database\Seeder;

class EngineSeeder extends Seeder
{
    public function run(): void
    {
        $engines = [
            ['engine_number' => 'ENG-001', 'engine_type' => 'Diesel Electric', 'fuel_type' => 'diesel', 'capacity' => 4200, 'condition' => 'active'],
            ['engine_number' => 'ENG-002', 'engine_type' => 'Electric AC', 'fuel_type' => 'electric', 'capacity' => 5000, 'condition' => 'active'],
            ['engine_number' => 'ENG-003', 'engine_type' => 'Diesel Hydraulic', 'fuel_type' => 'diesel', 'capacity' => 3800, 'condition' => 'maintenance'],
            ['engine_number' => 'ENG-004', 'engine_type' => 'Hybrid Regional', 'fuel_type' => 'hybrid', 'capacity' => 4600, 'condition' => 'active'],
            ['engine_number' => 'ENG-005', 'engine_type' => 'Electric High-Speed', 'fuel_type' => 'electric', 'capacity' => 6500, 'condition' => 'active'],
            ['engine_number' => 'ENG-006', 'engine_type' => 'Diesel Cargo', 'fuel_type' => 'diesel', 'capacity' => 5500, 'condition' => 'retired'],
        ];

        foreach ($engines as $engine) {
            Engine::updateOrCreate(
                ['engine_number' => $engine['engine_number']],
                [
                    'engine_type' => $engine['engine_type'],
                    'fuel_type' => $engine['fuel_type'],
                    'capacity' => $engine['capacity'],
                    'condition' => $engine['condition'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}