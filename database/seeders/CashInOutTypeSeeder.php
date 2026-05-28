<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CashInOutType;

class CashInOutTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'code' => 'B_BAKU',
                'name' => 'Bahan Baku',
                'description' => 'Pengeluaran untuk bahan baku',
                'is_income' => false,
                'sort_order' => 1,
            ],
            [
                'code' => 'PERALATAN',
                'name' => 'Peralatan',
                'description' => 'Pengeluaran untuk peralatan',
                'is_income' => false,
                'sort_order' => 2,
            ],
            [
                'code' => 'BAND',
                'name' => 'Band',
                'description' => 'Pengeluaran untuk band',
                'is_income' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'LISTRIK',
                'name' => 'Listrik',
                'description' => 'Pengeluaran untuk listrik',
                'is_income' => false,
                'sort_order' => 4,
            ],
            [
                'code' => 'GAS',
                'name' => 'Gas',
                'description' => 'Pengeluaran untuk gas',
                'is_income' => false,
                'sort_order' => 5,
            ],
            [
                'code' => 'REFUND',
                'name' => 'Refund',
                'description' => 'Pengeluaran untuk refund',
                'is_income' => false,
                'sort_order' => 6,
            ],
            [
                'code' => 'KASBON',
                'name' => 'Kasbon',
                'description' => 'Pengeluaran untuk kasbon',
                'is_income' => false,
                'sort_order' => 7,
            ],
            [
                'code' => 'OWNER',
                'name' => 'Owner',
                'description' => 'Pengeluaran untuk owner',
                'is_income' => false,
                'sort_order' => 8,
            ],
            [
                'code' => 'COMPLIMENT',
                'name' => 'Compliment',
                'description' => 'Pengeluaran untuk compliment',
                'is_income' => false,
                'sort_order' => 9,
            ],
            [
                'code' => 'BPJS',
                'name' => 'BPJS',
                'description' => 'Pengeluaran untuk BPJS',
                'is_income' => false,
                'sort_order' => 10,
            ],
            [
                'code' => 'QRIS',
                'name' => 'QRIS',
                'description' => 'Pemasukan melalui QRIS',
                'is_income' => true,
                'sort_order' => 11,
            ],
            [
                'code' => 'TUNAI',
                'name' => 'Tunai',
                'description' => 'Pemasukan melalui tunai',
                'is_income' => true,
                'sort_order' => 12,
            ],
            [
                'code' => 'PAJAK',
                'name' => 'Pajak',
                'description' => 'Pengeluaran untuk pajak',
                'is_income' => false,
                'sort_order' => 13,
            ],
            [
                'code' => 'TAX',
                'name' => 'Tax',
                'description' => 'Pengeluaran untuk tax',
                'is_income' => false,
                'sort_order' => 14,
            ],
            [
                'code' => 'GAJI',
                'name' => 'Gaji',
                'description' => 'Pengeluaran untuk gaji',
                'is_income' => false,
                'sort_order' => 15,
            ],
            [
                'code' => 'GAJI_C_PIRING',
                'name' => 'Gaji Cuci Piring',
                'description' => 'Pengeluaran untuk gaji cuci piring',
                'is_income' => false,
                'sort_order' => 16,
            ],
            [
                'code' => 'BB_MAKASSAR',
                'name' => 'Bahan Baku Makassar',
                'description' => 'Pengeluaran untuk bahan baku Makassar',
                'is_income' => false,
                'sort_order' => 17,
            ]
        ];

        foreach ($types as $type) {
            CashInOutType::create($type);
        }
    }
}
