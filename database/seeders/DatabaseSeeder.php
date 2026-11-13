<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\SystemRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Development seeding is disabled outside local and testing environments.');
        }

        $this->call(RolePermissionSeeder::class);
        $this->call(SettingsSeeder::class);

        foreach ([
            ['name' => 'Super Admin', 'email' => 'super-admin@example.com', 'role' => SystemRole::SUPER_ADMIN],
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'role' => SystemRole::ADMIN],
            ['name' => 'Manager User', 'email' => 'manager@example.com', 'role' => SystemRole::MANAGER],
            ['name' => 'Staff User', 'email' => 'staff@example.com', 'role' => SystemRole::STAFF],
            ['name' => 'Standard User', 'email' => 'user@example.com', 'role' => SystemRole::STANDARD_USER],
        ] as $persona) {
            User::query()->updateOrCreate(['email' => $persona['email']], [
                'name' => $persona['name'],
                'password' => Hash::make('password'),
            ])->syncRoles([$persona['role']]);
        }
    }
}
