<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CashInOutType;

class UpdateCashInOutTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashinout:update-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update tipe-tipe transaksi untuk ditampilkan di hasil akhir';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Tipe-tipe yang akan ditampilkan di hasil akhir
        $typesToShow = [
            'B_BAKU', 'PERALATAN', 'BAND', 'LISTRIK', 'GAS',
            'PAJAK', 'TAX', 'GAJI', 'GAJI_C_PIRING', 'COMPLIMENT',
            'KASBON', 'BPJS', 'OWNER', 'REFUND', 'BB_MAKASSAR'
        ];

        // Update tipe-tipe tersebut
        $count = CashInOutType::whereIn('code', $typesToShow)
            ->update(['show_akhir' => true]);

        $this->info("Berhasil memperbarui $count tipe transaksi untuk ditampilkan di hasil akhir.");

        return Command::SUCCESS;
    }
}
