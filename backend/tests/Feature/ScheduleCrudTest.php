<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\ScheduleStation;
use App\Models\Station;
use App\Models\Train;
use App\Models\TrainRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScheduleCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_schedule(): void
    {
        $admin = $this->makeAdminUser();
        Sanctum::actingAs($admin);

        $schedule = Schedule::factory()->create();
        $train = Train::factory()->create();
        $route = TrainRoute::factory()->create();

        $response = $this->putJson('/api/schedules/'.$schedule->id, [
            'train_id' => $train->id,
            'route_id' => $route->id,
            'departure_time' => '20:00:00',
            'is_active' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('schedule.train_id', $train->id)
            ->assertJsonPath('schedule.route_id', $route->id)
            ->assertJsonPath('schedule.is_active', false);
    }

    public function test_admin_can_patch_schedule_station_by_id(): void
    {
        $admin = $this->makeAdminUser();
        Sanctum::actingAs($admin);

        $schedule = Schedule::factory()->create();
        $stationOne = Station::factory()->create();
        $stationTwo = Station::factory()->create();

        $scheduleStation = ScheduleStation::factory()->create([
            'schedule_id' => $schedule->id,
            'station_id' => $stationOne->id,
            'sequence' => 1,
        ]);

        $response = $this->patchJson('/api/schedules/'.$schedule->id.'/stations/'.$scheduleStation->id, [
            'station_id' => $stationTwo->id,
            'sequence' => 2,
            'arrival_time' => '20:30:00',
            'departure_time' => '20:35:00',
        ]);

        $response->assertOk()
            ->assertJsonPath('schedule_station.station_id', $stationTwo->id)
            ->assertJsonPath('schedule_station.sequence', 2)
            ->assertJsonPath('schedule_station.updated_by', $admin->id);
    }

    public function test_admin_can_patch_schedule_station_by_station_id(): void
    {
        $admin = $this->makeAdminUser();
        Sanctum::actingAs($admin);

        $schedule = Schedule::factory()->create();
        $station = Station::factory()->create();
        $updatedStation = Station::factory()->create();

        ScheduleStation::factory()->create([
            'schedule_id' => $schedule->id,
            'station_id' => $station->id,
            'sequence' => 1,
        ]);

        $response = $this->patchJson('/api/schedules/'.$schedule->id.'/stations/by-station/'.$station->id, [
            'station_id' => $updatedStation->id,
            'sequence' => 2,
        ]);

        $response->assertOk()
            ->assertJsonPath('schedule_station.station_id', $updatedStation->id)
            ->assertJsonPath('schedule_station.sequence', 2);
    }

    public function test_member_cannot_access_schedule_crud(): void
    {
        $member = $this->makeMemberUser();
        Sanctum::actingAs($member);

        $schedule = Schedule::factory()->create();

        $this->putJson('/api/schedules/'.$schedule->id, [
            'is_active' => false,
        ])->assertForbidden();
    }

    private function makeAdminUser(): User
    {
        $this->ensureRole('admin');

        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
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
