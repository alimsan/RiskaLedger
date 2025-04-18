<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat permission
        $permissions = [
            'manage-users',
            'manage-roles',
            'manage-cash-in-out',
            'manage-cash-in-out-types',
            'view-reports'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Buat role admin dan berikan semua permission
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo($permissions);

        // Buat user admin
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('123.dmn'),
        ]);

        // Assign role admin ke user
        $admin->assignRole('admin');

        // Buat role operator dengan permission terbatas
        $operatorRole = Role::create(['name' => 'operator']);
        $operatorRole->givePermissionTo([
            'manage-cash-in-out',
            'view-reports'
        ]);

        // Buat role manajer dengan permission menengah
        $managerRole = Role::create(['name' => 'manager']);
        $managerRole->givePermissionTo([
            'manage-cash-in-out',
            'manage-cash-in-out-types',
            'view-reports'
        ]);
    }
}
