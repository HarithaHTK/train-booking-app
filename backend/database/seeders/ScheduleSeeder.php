<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\ScheduleStation;
use App\Models\Train;
use App\Models\TrainRoute;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $trains = Train::query()->orderBy('id')->get();
        $routes = TrainRoute::query()->with(['routeStations.station'])->orderBy('id')->get();

        if ($trains->isEmpty() || $routes->isEmpty()) {
            return;
        }

        $demoSchedules = [
            [
                'train' => $trains[0],
                'route' => $routes[0],
                'departure_time' => '06:30:00',
                'station_times' => [
                    ['arrival_time' => null, 'departure_time' => '06:30:00'],
                    ['arrival_time' => '07:10:00', 'departure_time' => '07:15:00'],
                    ['arrival_time' => '08:00:00', 'departure_time' => null],
                ],
            ],
            [
                'train' => $trains[1] ?? $trains[0],
                'route' => $routes[1] ?? $routes[0],
                'departure_time' => '09:00:00',
                'station_times' => [
                    ['arrival_time' => null, 'departure_time' => '09:00:00'],
                    ['arrival_time' => '09:40:00', 'departure_time' => '09:45:00'],
                    ['arrival_time' => '10:35:00', 'departure_time' => null],
                ],
            ],
            [
                'train' => $trains[0],
                'route' => $routes[2] ?? $routes[0],
                'departure_time' => '12:15:00',
                'station_times' => [
                    ['arrival_time' => null, 'departure_time' => '12:15:00'],
                    ['arrival_time' => '13:05:00', 'departure_time' => '13:10:00'],
                    ['arrival_time' => '14:00:00', 'departure_time' => null],
                ],
            ],
            [
                'train' => $trains[1] ?? $trains[0],
                'route' => $routes[3] ?? $routes[0],
                'departure_time' => '17:45:00',
                'station_times' => [
                    ['arrival_time' => null, 'departure_time' => '17:45:00'],
                    ['arrival_time' => '18:25:00', 'departure_time' => '18:30:00'],
                    ['arrival_time' => '19:20:00', 'departure_time' => '19:25:00'],
                    ['arrival_time' => '20:10:00', 'departure_time' => null],
                ],
            ],
        ];

        foreach ($demoSchedules as $index => $demoSchedule) {
            $routeStations = $demoSchedule['route']->routeStations
                ->sortBy('sequence')
                ->values();

            if ($routeStations->isEmpty()) {
                continue;
            }

            $schedule = Schedule::updateOrCreate(
                [
                    'train_id' => $demoSchedule['train']->id,
                    'route_id' => $demoSchedule['route']->id,
                    'departure_time' => $demoSchedule['departure_time'],
                ],
                [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            ScheduleStation::withTrashed()
                ->where('schedule_id', $schedule->id)
                ->forceDelete();

            foreach ($routeStations as $routeIndex => $routeStation) {
                $timeSet = $demoSchedule['station_times'][$routeIndex] ?? [
                    'arrival_time' => null,
                    'departure_time' => null,
                ];

                ScheduleStation::create([
                    'schedule_id' => $schedule->id,
                    'station_id' => $routeStation->station_id,
                    'sequence' => $routeStation->sequence,
                    'arrival_time' => $timeSet['arrival_time'],
                    'departure_time' => $timeSet['departure_time'],
                ]);
            }
        }
    }
}
