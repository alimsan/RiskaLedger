<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\Tenant;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cek semua tenant di sistem
        $tenants = Tenant::where('is_active', true)->get();

        if ($tenants->isEmpty()) {
            $this->command->info('Tidak ada tenant aktif ditemukan.');
            return;
        }

        // Daftar contoh produk
        $items = [
            [
                'name' => 'Nasi Goreng',
                'description' => 'Nasi goreng spesial dengan telur, ayam, dan sayuran',
                'price' => 25000,
                'category' => 'Makanan Utama',
                'is_active' => true,
            ],
            [
                'name' => 'Mie Goreng',
                'description' => 'Mie goreng dengan telur, bakso, dan sayuran',
                'price' => 23000,
                'category' => 'Makanan Utama',
                'is_active' => true,
            ],
            [
                'name' => 'Ayam Goreng',
                'description' => 'Ayam goreng renyah dengan bumbu spesial',
                'price' => 30000,
                'category' => 'Makanan Utama',
                'is_active' => true,
            ],
            [
                'name' => 'Es Teh',
                'description' => 'Teh manis dingin segar',
                'price' => 8000,
                'category' => 'Minuman',
                'is_active' => true,
            ],
            [
                'name' => 'Es Jeruk',
                'description' => 'Jus jeruk segar dengan es',
                'price' => 10000,
                'category' => 'Minuman',
                'is_active' => true,
            ],
            [
                'name' => 'Kopi Hitam',
                'description' => 'Kopi hitam nikmat tanpa gula',
                'price' => 12000,
                'category' => 'Minuman',
                'is_active' => true,
            ],
            [
                'name' => 'Kentang Goreng',
                'description' => 'Kentang goreng renyah dengan saus sambal',
                'price' => 15000,
                'category' => 'Camilan',
                'is_active' => true,
            ],
            [
                'name' => 'Pisang Goreng',
                'description' => 'Pisang goreng dengan tepung crispy',
                'price' => 12000,
                'category' => 'Camilan',
                'is_active' => true,
            ],
            [
                'name' => 'Cheese Cake',
                'description' => 'Kue keju lembut dengan topping strawberry',
                'price' => 20000,
                'category' => 'Dessert',
                'is_active' => true,
            ],
            [
                'name' => 'Pudding Coklat',
                'description' => 'Pudding coklat lembut dengan saus vanilla',
                'price' => 15000,
                'category' => 'Dessert',
                'is_active' => true,
            ],
        ];

        // Buat item untuk setiap tenant
        foreach ($tenants as $tenant) {
            $this->command->info("Menambahkan item untuk tenant: {$tenant->name}");

            foreach ($items as $item) {
                $item['tenant_id'] = $tenant->id;
                $item['stock'] = rand(5, 50); // Tambahkan stok random

                Item::create($item);
            }
        }

        $this->command->info('Seeder item selesai!');
    }
}
