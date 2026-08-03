<?php

namespace Database\Seeders;

use App\Models\Station;
use Illuminate\Database\Seeder;

class StationSeeder extends Seeder
{
    public function run(): void
    {
        $stations = [
            ['name' => 'Central Station', 'address' => '123 Main Street, Downtown'],
            ['name' => 'North Terminal', 'address' => '456 North Avenue, North District'],
            ['name' => 'South Terminal', 'address' => '789 South Boulevard, South City'],
            ['name' => 'East Junction', 'address' => '321 East Road, East Town'],
            ['name' => 'West Hub', 'address' => '654 West Parkway, West County'],
            ['name' => 'Airport Station', 'address' => '987 Sky Boulevard, Airport Zone'],
            ['name' => 'Harbor Terminal', 'address' => '111 Waterfront Drive, Harbor District'],
            ['name' => 'University Station', 'address' => '222 Academic Lane, University Area'],
        ];

        foreach ($stations as $station) {
            Station::updateOrCreate(
                ['name' => $station['name']],
                [
                    'address' => $station['address'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
