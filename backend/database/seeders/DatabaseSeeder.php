<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        // Create roles (idempotent)
        $roles = ['admin', 'member', 'guest'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r]);
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
        $admin->assignRole('admin');

        // Ensure member user exists and has member role
        $member = User::updateOrCreate(
            ['email' => 'member@email.com'],
            [
                'name' => 'Member',
                'password' => Hash::make('Password@123'),
                'email_verified_at' => now(),
            ]
        );
        $member->assignRole('member');

        // Ensure super admin exists and has both admin + member roles
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@email.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Password@123'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->syncRoles(['admin', 'member']);
    }
}
