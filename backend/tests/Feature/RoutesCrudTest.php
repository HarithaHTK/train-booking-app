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

class RoutesCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_routes_with_station_order(): void
    {
        $admin = $this->makeAdminUser();
        $route = TrainRoute::factory()->create([
            'name' => 'Main Line',
        ]);

        $stationOne = Station::factory()->create(['name' => 'Alpha Station']);
        $stationTwo = Station::factory()->create(['name' => 'Beta Station']);
        $stationThree = Station::factory()->create(['name' => 'Gamma Station']);

        RouteStation::factory()->create([
            'route_id' => $route->id,
            'station_id' => $stationTwo->id,
            'sequence' => 2,
        ]);
        RouteStation::factory()->create([
            'route_id' => $route->id,
            'station_id' => $stationOne->id,
            'sequence' => 1,
        ]);
        RouteStation::factory()->create([
            'route_id' => $route->id,
            'station_id' => $stationThree->id,
            'sequence' => 3,
        ]);

        Sanctum::actingAs($admin);

        $ascResponse = $this->getJson('/api/routes?order=asc');

        $ascResponse->assertOk();
        $this->assertSame([1, 2, 3], array_column($ascResponse->json('routes.0.stations'), 'sequence'));

        $descResponse = $this->getJson('/api/routes?order=desc');

        $descResponse->assertOk();
        $this->assertSame([3, 2, 1], array_column($descResponse->json('routes.0.stations'), 'sequence'));
    }

    public function test_admin_can_create_update_and_delete_route(): void
    {
        $admin = $this->makeAdminUser();
        Sanctum::actingAs($admin);

        $createResponse = $this->postJson('/api/routes', [
            'name' => 'Coastal Line',
            'description' => 'Route along the coast',
            'is_active' => true,
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('route.name', 'Coastal Line')
            ->assertJsonPath('route.is_active', true);

        $routeId = $createResponse->json('route.id');

        $updateResponse = $this->putJson('/api/routes/'.$routeId, [
            'name' => 'Updated Coastal Line',
            'description' => 'Updated route description',
            'is_active' => false,
        ]);

        $updateResponse->assertOk()
            ->assertJsonPath('route.name', 'Updated Coastal Line')
            ->assertJsonPath('route.is_active', false)
            ->assertJsonPath('route.updated_by', $admin->id);

        $this->assertDatabaseHas('routes', [
            'id' => $routeId,
            'name' => 'Updated Coastal Line',
            'updated_by' => $admin->id,
        ]);

        $route = TrainRoute::factory()->create([
            'name' => 'Cascade Line',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $station = Station::factory()->create();
        RouteStation::factory()->create([
            'route_id' => $route->id,
            'station_id' => $station->id,
            'sequence' => 1,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $deleteResponse = $this->deleteJson('/api/routes/'.$route->id);

        $deleteResponse->assertOk()
            ->assertJsonPath('message', 'Route deleted successfully.');

        $this->assertSoftDeleted('routes', [
            'id' => $route->id,
        ]);

        $this->assertSoftDeleted('route_stations', [
            'route_id' => $route->id,
        ]);
    }

    public function test_member_cannot_access_route_crud(): void
    {
        $member = $this->makeMemberUser();
        Sanctum::actingAs($member);

        $response = $this->getJson('/api/routes');

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