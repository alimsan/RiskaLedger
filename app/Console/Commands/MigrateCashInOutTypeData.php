<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\CashInOutType;
use App\Models\mCashInOut;

class MigrateCashInOutTypeData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-cash-in-out-type-data {--force : Paksa jalankan migrasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrasi data dari kolom type ke type_id pada tabel cash_in_out';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai migrasi data dari type ke type_id...');

        // Periksa status migrasi
        $migrationName = 'cash_in_out_type_migration';
        $migrationStatus = DB::table('data_migration_status')
            ->where('migration_name', $migrationName)
            ->first();

        if ($migrationStatus && $migrationStatus->is_completed && !$this->option('force')) {
            $this->info('Migrasi sudah pernah dijalankan. Gunakan --force untuk menjalankan kembali.');
            return;
        }

        // Ambil data type unik dari tabel cash_in_out
        $uniqueTypes = DB::table('cash_in_out')
            ->select('type')
            ->distinct()
            ->whereNotNull('type')
            ->pluck('type');

        $this->info('Ditemukan ' . count($uniqueTypes) . ' tipe unik.');

        // Pastikan semua tipe terdaftar di cash_in_out_types
        foreach ($uniqueTypes as $type) {
            $cashInOutType = CashInOutType::firstOrCreate(
                ['code' => $type],
                [
                    'name' => $type,
                    'description' => 'Auto-generated from migration',
                    'is_income' => in_array($type, ['QRIS', 'TUNAI']),
                ]
            );

            $this->info("Tipe {$type} " . ($cashInOutType->wasRecentlyCreated ? 'dibuat' : 'sudah ada') . ".");
        }

        // Ambil mapping kode ke id
        $typeMapping = CashInOutType::pluck('id', 'code')->toArray();

        // Hitung total record
        $totalRecords = DB::table('cash_in_out')
            ->whereNull('type_id')
            ->whereNotNull('type')
            ->count();

        $this->info("Total {$totalRecords} record akan dimigrasi.");

        // Update data_migration_status
        $migrationStatusData = [
            'migration_name' => $migrationName,
            'is_completed' => false,
            'started_at' => now(),
            'total_records' => $totalRecords,
            'processed_records' => 0
        ];

        if ($migrationStatus) {
            DB::table('data_migration_status')
                ->where('migration_name', $migrationName)
                ->update($migrationStatusData);
        } else {
            DB::table('data_migration_status')->insert($migrationStatusData);
        }

        // Migrasi data
        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();
        $processedRecords = 0;

        foreach ($typeMapping as $code => $id) {
            $updated = DB::table('cash_in_out')
                ->where('type', $code)
                ->whereNull('type_id')
                ->update(['type_id' => $id]);

            $processedRecords += $updated;
            $bar->advance($updated);

            // Update status
            DB::table('data_migration_status')
                ->where('migration_name', $migrationName)
                ->update([
                    'processed_records' => $processedRecords,
                    'notes' => "Memproses {$code}: {$updated} record."
                ]);
        }

        $bar->finish();
        $this->newLine();

        // Update status
        DB::table('data_migration_status')
            ->where('migration_name', $migrationName)
            ->update([
                'is_completed' => true,
                'completed_at' => now(),
                'notes' => "Migrasi selesai. {$processedRecords} dari {$totalRecords} record berhasil dimigrasi."
            ]);

        $this->info("Migrasi selesai. {$processedRecords} dari {$totalRecords} record berhasil dimigrasi.");
    }
}
