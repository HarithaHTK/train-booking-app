<?php

namespace Database\Seeders;

use App\Models\Coach;
use Illuminate\Database\Seeder;

class CoachSeeder extends Seeder
{
    public function run(): void
    {
        $coaches = [];

        for ($i = 1; $i <= 12; $i++) {
            $coaches[] = [
                'coach_number' => sprintf('RES-%03d', $i),
                'coach_type' => 'reserved',
                'total_seats' => 10,
            ];
        }

        for ($i = 1; $i <= 20; $i++) {
            $coaches[] = [
                'coach_number' => sprintf('UNR-%03d', $i),
                'coach_type' => 'unreserved',
                'total_seats' => 10,
            ];
        }

        foreach ($coaches as $coach) {
            Coach::updateOrCreate(
                ['coach_number' => $coach['coach_number']],
                [
                    'coach_type' => $coach['coach_type'],
                    'total_seats' => $coach['total_seats'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}