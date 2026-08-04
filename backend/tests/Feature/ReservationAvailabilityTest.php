<?php

namespace Tests\Feature;

use App\Models\Coach;
use App\Models\Reservation;
use App\Models\Schedule;
use App\Models\ScheduleStation;
use App\Models\Seat;
use App\Models\Station;
use App\Models\Train;
use App\Models\TrainRoute;
use App\Models\TrainRoute as RouteModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReservationAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_seat_can_be_reserved_on_different_travel_dates(): void
    {
        $user = $this->makeMemberUser();
        Sanctum::actingAs($user);

        $schedule = $this->makeSchedule();
        $seat = $this->makeSeat();
        $stations = $this->makeStationsForSchedule($schedule);

        $this->postJson('/api/reservations', $this->payload($schedule->id, $seat->id, $stations['start']->id, $stations['end']->id, '2026-08-04'))
            ->assertCreated();

        $this->postJson('/api/reservations', $this->payload($schedule->id, $seat->id, $stations['start']->id, $stations['end']->id, '2026-08-05'))
            ->assertCreated();

        $this->assertDatabaseCount('reservations', 2);
    }

    public function test_same_seat_cannot_be_reserved_twice_for_same_schedule_and_date(): void
    {
        $user = $this->makeMemberUser();
        Sanctum::actingAs($user);

        $schedule = $this->makeSchedule();
        $seat = $this->makeSeat();
        $stations = $this->makeStationsForSchedule($schedule);

        $this->postJson('/api/reservations', $this->payload($schedule->id, $seat->id, $stations['start']->id, $stations['end']->id, '2026-08-04'))
            ->assertCreated();

        $this->postJson('/api/reservations', $this->payload($schedule->id, $seat->id, $stations['start']->id, $stations['end']->id, '2026-08-04'))
            ->assertStatus(422)
            ->assertJsonFragment(['One or more selected seats are already reserved for this journey.']);
    }

    public function test_reservation_payload_exposes_is_reserved_flag(): void
    {
        $user = $this->makeMemberUser();
        Sanctum::actingAs($user);

        $schedule = $this->makeSchedule();
        $seat = $this->makeSeat();
        $stations = $this->makeStationsForSchedule($schedule);

        $response = $this->postJson('/api/reservations', $this->payload($schedule->id, $seat->id, $stations['start']->id, $stations['end']->id, '2026-08-04'))
            ->assertCreated();

        $response->assertJsonPath('reservation.isReserved', true);
        $response->assertJsonPath('reservations.0.isReserved', true);

        $this->getJson('/api/reservations?schedule_id='.$schedule->id.'&travel_date=2026-08-04')
            ->assertOk()
            ->assertJsonPath('reservations.0.isReserved', true);
    }

    public function test_schedule_seat_reserved_flag_respects_travel_date(): void
    {
        $user = $this->makeMemberUser();
        Sanctum::actingAs($user);

        $schedule = $this->makeSchedule();
        $seat = $this->makeSeat();
        $stations = $this->makeStationsForSchedule($schedule);

        $this->postJson('/api/reservations', $this->payload($schedule->id, $seat->id, $stations['start']->id, $stations['end']->id, '2026-08-04'))
            ->assertCreated();

        $this->getJson('/api/schedules/'.$schedule->id.'?travel_date=2026-08-04')
            ->assertOk()
            ->assertJsonPath('schedule.train.coaches.0.seats.0.isReserved', true);

        $this->getJson('/api/schedules/'.$schedule->id.'?travel_date=2026-08-05')
            ->assertOk()
            ->assertJsonPath('schedule.train.coaches.0.seats.0.isReserved', false);
    }

    private function payload(int $scheduleId, int $seatId, int $startStationId, int $leaveStationId, string $travelDate): array
    {
        return [
            'schedule_id' => $scheduleId,
            'start_station_id' => $startStationId,
            'leave_station_id' => $leaveStationId,
            'seat_id' => $seatId,
            'travel_date' => $travelDate,
            'status' => 'confirmed',
        ];
    }

    private function makeSchedule(): Schedule
    {
        $route = TrainRoute::factory()->create();
        $train = Train::factory()->create();

        return Schedule::factory()->create([
            'route_id' => $route->id,
            'train_id' => $train->id,
        ]);
    }

    private function makeSeat(): Seat
    {
        $coach = Coach::factory()->create();

        return Seat::factory()->create([
            'coach_id' => $coach->id,
        ]);
    }

    private function makeStationsForSchedule(Schedule $schedule): array
    {
        $start = Station::factory()->create();
        $end = Station::factory()->create();

        ScheduleStation::factory()->create([
            'schedule_id' => $schedule->id,
            'station_id' => $start->id,
            'sequence' => 1,
        ]);

        ScheduleStation::factory()->create([
            'schedule_id' => $schedule->id,
            'station_id' => $end->id,
            'sequence' => 2,
        ]);

        return compact('start', 'end');
    }

    private function makeMemberUser(): User
    {
        $this->ensureRole('member');

        $user = User::factory()->create();
        $user->assignRole('member');

        return $user;
    }

    private function ensureRole(string $name): void
    {
        Role::findOrCreate($name, config('auth.defaults.guard', 'web'));
    }
}