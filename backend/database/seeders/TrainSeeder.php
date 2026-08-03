<?php

namespace Database\Seeders;

use App\Models\Coach;
use App\Models\Engine;
use App\Models\Train;
use App\Models\TrainCoach;
use App\Models\TrainEngine;
use App\Models\TrainRoute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrainSeeder extends Seeder
{
    public function run(): void
    {
        $routes = TrainRoute::query()->orderBy('id')->get();
        $engines = Engine::query()->orderBy('id')->get();
        $reservedCoaches = Coach::query()->where('coach_type', 'reserved')->orderBy('id')->get();
        $unreservedCoaches = Coach::query()->where('coach_type', 'unreserved')->orderBy('id')->get();

        if ($routes->count() < 2 || $engines->count() < 2 || $reservedCoaches->count() < 6 || $unreservedCoaches->count() < 10) {
            return;
        }

        $demoTrains = [
            [
                'train_number' => 'TR-001',
                'train_name' => 'Demo Express 1',
                'route_id' => $routes[0]->id,
                'engine_id' => $engines[0]->id,
                'reserved_coach_ids' => $reservedCoaches->slice(0, 3)->pluck('id')->all(),
                'unreserved_coach_ids' => $unreservedCoaches->slice(0, 5)->pluck('id')->all(),
            ],
            [
                'train_number' => 'TR-002',
                'train_name' => 'Demo Express 2',
                'route_id' => $routes[1]->id,
                'engine_id' => $engines[1]->id,
                'reserved_coach_ids' => $reservedCoaches->slice(3, 3)->pluck('id')->all(),
                'unreserved_coach_ids' => $unreservedCoaches->slice(5, 5)->pluck('id')->all(),
            ],
        ];

        foreach ($demoTrains as $demoTrain) {
            $train = Train::updateOrCreate(
                ['train_number' => $demoTrain['train_number']],
                [
                    'train_name' => $demoTrain['train_name'],
                    'route_id' => $demoTrain['route_id'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            DB::table('train_engines')->where('train_id', $train->id)->delete();
            DB::table('train_coaches')->where('train_id', $train->id)->delete();

            TrainEngine::create([
                'train_id' => $train->id,
                'engine_id' => $demoTrain['engine_id'],
                'position' => 1,
            ]);

            $coachIds = array_merge($demoTrain['reserved_coach_ids'], $demoTrain['unreserved_coach_ids']);

            foreach ($coachIds as $position => $coachId) {
                TrainCoach::create([
                    'train_id' => $train->id,
                    'coach_id' => $coachId,
                    'position' => $position + 1,
                ]);
            }
        }
    }
}