<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Define admin roles with Vietnamese Names
        $superAdminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'admin']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'CSKH', 'guard_name' => 'admin']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Đại lý', 'guard_name' => 'admin']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Kế toán', 'guard_name' => 'admin']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Người dùng', 'guard_name' => 'admin']);

        // Define permissions for settings
        $viewPermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'settings.view', 'guard_name' => 'admin']);
        $updatePermission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'settings.update', 'guard_name' => 'admin']);
        $superAdminRole->givePermissionTo([$viewPermission, $updatePermission]);

        // Create Super Admin
        $admin = \App\Models\AdminUser::firstOrCreate([
            'email' => 'admin@solar.local'
        ], [
            'name' => 'Super Admin',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $admin->assignRole($superAdminRole);
    }
}
