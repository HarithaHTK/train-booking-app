<?php

namespace Tests\Feature;

use App\Models\Station;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StationCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_stations(): void
    {
        $admin = $this->makeAdminUser();
        Station::factory()->create([
            'name' => 'Central Station',
            'address' => '123 Main Street',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/stations');

        $response->assertOk()
            ->assertJsonCount(1, 'stations')
            ->assertJsonPath('stations.0.name', 'Central Station');
    }

    public function test_admin_can_create_station(): void
    {
        $admin = $this->makeAdminUser();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/stations', [
            'name' => 'Central Station',
            'address' => '123 Main Street',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('station.name', 'Central Station')
            ->assertJsonPath('station.is_active', true);

        $this->assertDatabaseHas('stations', [
            'name' => 'Central Station',
            'address' => '123 Main Street',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    public function test_admin_can_update_station(): void
    {
        $admin = $this->makeAdminUser();
        $station = Station::factory()->create([
            'name' => 'Old Station',
            'address' => 'Old Address',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/stations/'.$station->id, [
            'name' => 'Updated Station',
            'address' => 'Updated Address',
            'is_active' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('station.name', 'Updated Station')
            ->assertJsonPath('station.is_active', false)
            ->assertJsonPath('station.updated_by', $admin->id);

        $this->assertDatabaseHas('stations', [
            'id' => $station->id,
            'name' => 'Updated Station',
            'address' => 'Updated Address',
            'updated_by' => $admin->id,
        ]);
    }

    public function test_admin_can_delete_station(): void
    {
        $admin = $this->makeAdminUser();
        $station = Station::factory()->create([
            'name' => 'Delete Station',
            'address' => 'Delete Address',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/stations/'.$station->id);

        $response->assertOk()
            ->assertJsonPath('message', 'Station deleted successfully.');

        $this->assertSoftDeleted('stations', [
            'id' => $station->id,
        ]);

        $this->assertDatabaseHas('stations', [
            'id' => $station->id,
            'deleted_by' => $admin->id,
        ]);
    }

    public function test_member_cannot_access_station_crud(): void
    {
        $member = $this->makeMemberUser();
        Sanctum::actingAs($member);

        $response = $this->getJson('/api/stations');

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
