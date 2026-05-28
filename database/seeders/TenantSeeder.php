<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\ProfitSharing;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default tenant
        $tenant = Tenant::create([
            'name' => 'Devasco',
            'address' => 'Jl. Urip Sumoharjo',
            'phone' => '08123456789',
            'email' => 'devasco@gmail.com',
            'description' => 'Devasco Cafe & Resto',
            'is_active' => true,
        ]);

        // Create default profit sharing
        ProfitSharing::create([
            'tenant_id' => $tenant->id,
            'name' => 'Laba 80%',
            'percentage' => 80.00,
            'is_active' => true,
            'is_default' => true,
        ]);

        ProfitSharing::create([
            'tenant_id' => $tenant->id,
            'name' => 'Laba 20%',
            'percentage' => 20.00,
            'is_active' => true,
            'is_default' => false,
        ]);
        $tenant1 = Tenant::create([
            'name' => 'Buana Ice Crystal',
            'address' => 'Jl. Example No. 123',
            'phone' => '08123456789',
            'email' => 'buanaice@gmail.com',
            'description' => 'Ice Crystal',
            'is_active' => true,
        ]);
        // Create default profit sharing
        ProfitSharing::create([
            'tenant_id' => $tenant1->id,
            'name' => 'Laba 50%',
            'percentage' => 50.00,
            'is_active' => true,
            'is_default' => true,
        ]);

        ProfitSharing::create([
            'tenant_id' => $tenant1->id,
            'name' => 'Laba 50%',
            'percentage' => 50.00,
            'is_active' => true,
            'is_default' => false,
        ]);
    }
}
