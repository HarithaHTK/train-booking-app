<?php

namespace Database\Seeders;

use App\Models\Station;
use App\Models\TrainRoute;
use App\Models\RouteStation;
use Illuminate\Database\Seeder;

class TrainRouteSeeder extends Seeder
{
    public function run(): void
    {
        // Define routes with their station sequences
        $routes = [
            [
                'name' => 'Express North',
                'description' => 'High-speed express service to northern destinations',
                'stations' => ['Central Station', 'North Terminal', 'Airport Station'],
            ],
            [
                'name' => 'Coastal Line',
                'description' => 'Scenic route along the coast',
                'stations' => ['Central Station', 'Harbor Terminal', 'South Terminal'],
            ],
            [
                'name' => 'University Express',
                'description' => 'Connect university and airport',
                'stations' => ['Airport Station', 'University Station', 'Central Station'],
            ],
            [
                'name' => 'East-West Connector',
                'description' => 'Cross-city route connecting east and west hubs',
                'stations' => ['West Hub', 'Central Station', 'East Junction', 'Airport Station'],
            ],
            [
                'name' => 'Full Circle',
                'description' => 'Complete circuit around the city',
                'stations' => ['Central Station', 'North Terminal', 'East Junction', 'South Terminal', 'West Hub'],
            ],
        ];

        foreach ($routes as $routeData) {
            $route = TrainRoute::updateOrCreate(
                ['name' => $routeData['name']],
                [
                    'description' => $routeData['description'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // Add stations to route in sequence
            foreach ($routeData['stations'] as $sequence => $stationName) {
                $station = Station::where('name', $stationName)->first();
                if ($station) {
                    RouteStation::updateOrCreate(
                        [
                            'route_id' => $route->id,
                            'station_id' => $station->id,
                        ],
                        [
                            'sequence' => $sequence + 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }
}
