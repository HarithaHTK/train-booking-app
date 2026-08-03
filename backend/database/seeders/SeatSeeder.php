<?php

namespace Database\Seeders;

use App\Models\Coach;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        $coaches = Coach::query()->orderBy('coach_number')->get();

        foreach ($coaches as $coach) {
            for ($seatIndex = 1; $seatIndex <= 10; $seatIndex++) {
                $seatNumber = sprintf('A%d', $seatIndex);

                Seat::updateOrCreate(
                    [
                        'coach_id' => $coach->id,
                        'seat_number' => $seatNumber,
                    ],
                    [
                        'seat_class' => null,
                        'is_reserved' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}