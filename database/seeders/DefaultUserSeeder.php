<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tenant;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil tenant default
        $tenant = Tenant::where('email','buanaice@gmail.com')->get();

       /*  // Buat superadmin (tidak terikat tenant)
        User::create([
            'name' => 'Superadmin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
        ]);

        // Buat admin (tidak terikat tenant)
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
 */
        // Buat owner untuk tenant default
        User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'tenant_id' => $tenant->id,
        ]);

        // Buat operator untuk tenant default
        User::create([
            'name' => 'Operator',
            'email' => 'operator@example.com',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'tenant_id' => $tenant->id,
        ]);
    }
}
