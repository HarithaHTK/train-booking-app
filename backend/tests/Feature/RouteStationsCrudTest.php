<?php

namespace Tests\Feature;

use App\Models\RouteStation;
use App\Models\Station;
use App\Models\TrainRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RouteStationsCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_route_station(): void
    {
        $admin = $this->makeAdminUser();
        $route = TrainRoute::factory()->create();
        $station = Station::factory()->create();
        $updatedStation = Station::factory()->create();
        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/api/route-stations', [
            'route_id' => $route->id,
            'station_id' => $station->id,
            'sequence' => 1,
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('route_station.route_id', $route->id)
            ->assertJsonPath('route_station.station_id', $station->id)
            ->assertJsonPath('route_station.sequence', 1);

        $routeStationId = $createResponse->json('route_station.id');

        $updateResponse = $this->putJson('/api/route-stations/'.$routeStationId, [
            'station_id' => $updatedStation->id,
            'sequence' => 2,
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('route_station.station_id', $updatedStation->id)
            ->assertJsonPath('route_station.sequence', 2)
            ->assertJsonPath('route_station.updated_by', $admin->id);

        $this->assertDatabaseHas('route_stations', [
            'id' => $routeStationId,
            'station_id' => $updatedStation->id,
            'sequence' => 2,
            'updated_by' => $admin->id,
        ]);

        $deleteResponse = $this->deleteJson('/api/route-stations/'.$routeStationId);

        $deleteResponse->assertOk()
            ->assertJsonPath('message', 'Route station deleted successfully.');

        $this->assertSoftDeleted('route_stations', [
            'id' => $routeStationId,
        ]);

        $this->assertDatabaseHas('route_stations', [
            'id' => $routeStationId,
            'deleted_by' => $admin->id,
        ]);
    }

    public function test_duplicate_station_cannot_be_added_to_same_route(): void
    {
        $admin = $this->makeAdminUser();
        Sanctum::actingAs($admin);

        $route = TrainRoute::factory()->create();
        $station = Station::factory()->create();

        $this->postJson('/api/route-stations', [
            'route_id' => $route->id,
            'station_id' => $station->id,
            'sequence' => 1,
        ])->assertCreated();

        $duplicateResponse = $this->postJson('/api/route-stations', [
            'route_id' => $route->id,
            'station_id' => $station->id,
            'sequence' => 2,
        ]);

        $duplicateResponse->assertStatus(422)
            ->assertJsonValidationErrors(['station_id']);
    }

    public function test_same_station_can_exist_in_different_routes(): void
    {
        $admin = $this->makeAdminUser();
        Sanctum::actingAs($admin);

        $station = Station::factory()->create();
        $routeOne = TrainRoute::factory()->create();
        $routeTwo = TrainRoute::factory()->create();

        $firstResponse = $this->postJson('/api/route-stations', [
            'route_id' => $routeOne->id,
            'station_id' => $station->id,
            'sequence' => 1,
        ]);

        $firstResponse->assertCreated();

        $secondResponse = $this->postJson('/api/route-stations', [
            'route_id' => $routeTwo->id,
            'station_id' => $station->id,
            'sequence' => 1,
        ]);

        $secondResponse->assertCreated()
            ->assertJsonPath('route_station.route_id', $routeTwo->id)
            ->assertJsonPath('route_station.station_id', $station->id);
    }

    public function test_member_cannot_access_route_station_crud(): void
    {
        $member = $this->makeMemberUser();
        Sanctum::actingAs($member);

        $response = $this->getJson('/api/route-stations');

        $response->assertForbidden();
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