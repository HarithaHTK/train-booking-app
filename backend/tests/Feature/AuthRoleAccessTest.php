<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_login_to_admin_app(): void
    {
        $user = User::factory()->create([
            'role' => 'member',
            'email' => 'member@example.com',
            'password' => Hash::make('Password@123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password@123',
            'app' => 'admin',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'This account cannot access the selected application.',
            ]);
    }

    public function test_admin_cannot_login_to_portal_app(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin-user@example.com',
            'password' => Hash::make('Password@123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password@123',
            'app' => 'portal',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'This account cannot access the selected application.',
            ]);
    }

    public function test_admin_can_login_to_admin_app(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'email' => 'real-admin@example.com',
            'password' => Hash::make('Password@123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password@123',
            'app' => 'admin',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'role'],
                'token',
            ])
            ->assertJsonPath('user.role', 'admin');
    }

    public function test_super_admin_can_login_to_admin_app(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'super-admin@example.com',
            'password' => Hash::make('Password@123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password@123',
            'app' => 'admin',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.role', 'super_admin');
    }

    public function test_super_admin_can_login_to_portal_app(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'super-admin-portal@example.com',
            'password' => Hash::make('Password@123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Password@123',
            'app' => 'portal',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.role', 'super_admin');
    }
}