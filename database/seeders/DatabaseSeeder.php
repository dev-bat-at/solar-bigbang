<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'admin']);
        Role::firstOrCreate(['name' => 'CSKH', 'guard_name' => 'admin']);
        Role::firstOrCreate(['name' => 'Đại lý', 'guard_name' => 'admin']);
        Role::firstOrCreate(['name' => 'Kế toán', 'guard_name' => 'admin']);
        Role::firstOrCreate(['name' => 'Người dùng', 'guard_name' => 'admin']);

        $viewPermission = Permission::firstOrCreate(['name' => 'settings.view', 'guard_name' => 'admin']);
        $updatePermission = Permission::firstOrCreate(['name' => 'settings.update', 'guard_name' => 'admin']);
        $superAdminRole->givePermissionTo([$viewPermission, $updatePermission]);

        $admin = \App\Models\AdminUser::firstOrCreate(
            ['email' => 'admin@solar.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole($superAdminRole);

        $this->call([
            DealerSeeder::class,
            UserSeeder::class,
            CustomerSeeder::class,
            ProductCategorySeeder::class,
            ProductSeeder::class,
            SystemTypeSeeder::class,
            SamplePostSeeder::class,
        ]);
    }
}
