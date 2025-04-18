<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\CashInOutType;
use App\Models\mCashInOut;
use Doctrine\DBAL\Types\Type;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite tidak mendukung ALTER COLUMN
        // Solusinya adalah dengan membuat tabel baru dengan skema yang diinginkan
        // Lalu copy data dari tabel lama ke tabel baru

        // 1. Buat tabel temporary
        Schema::create('cash_in_out_temp', function (Blueprint $table) {
            $table->id();
            $table->string('nama_barang')->nullable();
            $table->string('type', 50)->default('B_BAKU'); // Ubah enum menjadi string(50)
            $table->foreignId('type_id')->nullable()->constrained('cash_in_out_types')->onDelete('set null');
            $table->text('deksripsi')->nullable();
            $table->tinyInteger('tipe_cio')->default('2')->comment("no 1 pemasukan no 2 pengeluaran");
            $table->bigInteger('nilai');
            $table->dateTime('waktu');
            $table->timestamps();
        });

        // 2. Copy data dari tabel lama ke tabel baru
        DB::statement('INSERT INTO cash_in_out_temp SELECT id, nama_barang, type, NULL, deksripsi, tipe_cio, nilai, waktu, created_at, updated_at FROM cash_in_out');

        // 3. Drop tabel lama
        Schema::dropIfExists('cash_in_out');

        // 4. Rename tabel temporary menjadi tabel asli
        Schema::rename('cash_in_out_temp', 'cash_in_out');

        // Eksekusi migrasi data
        $this->migrateData();
    }

    /**
     * Memigrasikan data dari kolom type ke type_id
     */
    private function migrateData(): void
    {
        // Karena tabel cash_in_out_types sudah dibuat di migrasi sebelumnya
        // dan seeder mungkin sudah dijalankan, kita hanya perlu memastikan
        // semua tipe yang ada di cash_in_out juga ada di cash_in_out_types

        // Ambil semua tipe unik dari cash_in_out
        $uniqueTypes = DB::table('cash_in_out')
            ->select('type')
            ->distinct()
            ->whereNotNull('type')
            ->pluck('type');

        // Pastikan semua tipe terdaftar di cash_in_out_types
        foreach ($uniqueTypes as $type) {
            CashInOutType::firstOrCreate(
                ['code' => $type],
                [
                    'name' => $type,
                    'description' => 'Auto-generated from migration',
                    'is_income' => in_array($type, ['QRIS', 'TUNAI']),
                    'sort_order' => 0
                ]
            );
        }

        // Ambil mapping kode ke id
        $typeMapping = CashInOutType::pluck('id', 'code')->toArray();

        // Update type_id berdasarkan type
        foreach ($typeMapping as $code => $id) {
            DB::table('cash_in_out')
                ->where('type', $code)
                ->update(['type_id' => $id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu implementasi untuk rollback karena
        // data sudah ada di kolom type dan lebih baik menggunakan kolom string
    }
};
