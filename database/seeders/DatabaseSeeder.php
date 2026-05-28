<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            UserSeeder::class,
            // CashInOutType seeder sudah dijalankan dalam migrasi data
            // CashInOutTypeSeeder::class,
        ]);

        // Add TenantSeeder
        $this->call(TenantSeeder::class);

        // Add DefaultUserSeeder
        $this->call(DefaultUserSeeder::class);
    }
}
