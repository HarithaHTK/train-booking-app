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
            ->assertJsonPath('schedule.is_active', false)
            ->assertJsonPath('schedule.departure_time', '20:00:00');
    }

    public function test_admin_can_create_daily_schedule_with_time_only_departure(): void
    {
        $admin = $this->makeAdminUser();
        Sanctum::actingAs($admin);

        $train = Train::factory()->create();
        $route = TrainRoute::factory()->create();

        $response = $this->postJson('/api/schedules', [
            'train_id' => $train->id,
            'route_id' => $route->id,
            'departure_time' => '06:30:00',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('schedule.train_id', $train->id)
            ->assertJsonPath('schedule.route_id', $route->id)
            ->assertJsonPath('schedule.departure_time', '06:30:00')
            ->assertJsonPath('schedule.is_active', true);
    }

    public function test_admin_rejects_datetime_departure_time(): void
    {
        $admin = $this->makeAdminUser();
        Sanctum::actingAs($admin);

        $train = Train::factory()->create();
        $route = TrainRoute::factory()->create();

        $this->postJson('/api/schedules', [
            'train_id' => $train->id,
            'route_id' => $route->id,
            'departure_time' => '2026-08-04 06:30:00',
            'is_active' => true,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['departure_time']);
    }

    public function test_admin_rejects_datetime_station_times(): void
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

        $this->patchJson('/api/schedules/'.$schedule->id.'/stations/'.$scheduleStation->id, [
            'station_id' => $stationTwo->id,
            'sequence' => 2,
            'arrival_time' => '2026-08-04 20:30:00',
            'departure_time' => '2026-08-04 20:35:00',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['arrival_time', 'departure_time']);
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

    public function test_admin_can_search_routes_and_schedules_by_station_direction(): void
    {
        $admin = $this->makeAdminUser();
        Sanctum::actingAs($admin);

        $central = Station::factory()->create(['name' => 'Central Station']);
        $north = Station::factory()->create(['name' => 'North Terminal']);
        $airport = Station::factory()->create(['name' => 'Airport Station']);

        $route = TrainRoute::factory()->create([
            'name' => 'Northbound Line',
            'is_active' => true,
        ]);

        \App\Models\RouteStation::factory()->create([
            'route_id' => $route->id,
            'station_id' => $central->id,
            'sequence' => 1,
        ]);
        \App\Models\RouteStation::factory()->create([
            'route_id' => $route->id,
            'station_id' => $north->id,
            'sequence' => 2,
        ]);
        \App\Models\RouteStation::factory()->create([
            'route_id' => $route->id,
            'station_id' => $airport->id,
            'sequence' => 3,
        ]);

        $schedule = Schedule::factory()->create([
            'route_id' => $route->id,
        ]);

        $response = $this->getJson('/api/route-search/by-station/'.$central->id);

        $response->assertOk()
            ->assertJsonPath('station.id', $central->id)
            ->assertJsonCount(1, 'routes')
            ->assertJsonCount(1, 'schedules')
            ->assertJsonPath('routes.0.id', $route->id)
            ->assertJsonPath('schedules.0.id', $schedule->id);
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
