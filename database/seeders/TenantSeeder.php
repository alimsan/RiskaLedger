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
            'name' => 'Tenant Default',
            'address' => 'Jl. Example No. 123',
            'phone' => '08123456789',
            'email' => 'tenant@example.com',
            'description' => 'Tenant Default',
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
    }
}
