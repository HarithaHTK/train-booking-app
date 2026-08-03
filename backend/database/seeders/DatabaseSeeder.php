<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roleTable = config('permission.table_names.roles', 'roles');
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $guardName = config('auth.defaults.guard', 'web');

        // Create roles (idempotent)
        $roles = ['admin', 'member', 'guest'];
        foreach ($roles as $r) {
            DB::table($roleTable)->updateOrInsert(
                ['name' => $r, 'guard_name' => $guardName],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }

        // Ensure admin user exists and has admin role
        $admin = User::updateOrCreate(
            ['email' => 'admin@email.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Password@123'),
                'email_verified_at' => now(),
            ]
        );
        $this->attachRole($admin->id, 'admin', $roleTable, $modelHasRolesTable, $guardName);

        // Ensure member user exists and has member role
        $member = User::updateOrCreate(
            ['email' => 'member@email.com'],
            [
                'name' => 'Member',
                'password' => Hash::make('Password@123'),
                'email_verified_at' => now(),
            ]
        );
        $this->attachRole($member->id, 'member', $roleTable, $modelHasRolesTable, $guardName);

        // Ensure super admin exists and has both admin + member roles
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@email.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Password@123'),
                'email_verified_at' => now(),
            ]
        );
        $this->attachRole($superAdmin->id, 'admin', $roleTable, $modelHasRolesTable, $guardName);
        $this->attachRole($superAdmin->id, 'member', $roleTable, $modelHasRolesTable, $guardName);

        // Seed stations and routes
        $this->call(StationSeeder::class);
        $this->call(TrainRouteSeeder::class);

        // Seed train assets
        $this->call(EngineSeeder::class);
        $this->call(CoachSeeder::class);
        $this->call(SeatSeeder::class);

        // Seed demo trains for the admin table view
        $this->call(TrainSeeder::class);
    }

    private function attachRole(int $userId, string $roleName, string $roleTable, string $modelHasRolesTable, string $guardName): void
    {
        $roleId = DB::table($roleTable)
            ->where('name', $roleName)
            ->where('guard_name', $guardName)
            ->value('id');

        if (! $roleId) {
            return;
        }

        DB::table($modelHasRolesTable)->updateOrInsert(
            [
                'role_id' => $roleId,
                'model_type' => User::class,
                'model_id' => $userId,
            ],
            []
        );
    }
}
