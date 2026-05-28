<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cash_in_out', function (Blueprint $table) {
            // Cek apakah kolom keterangan sudah ada
            if (!Schema::hasColumn('cash_in_out', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('nilai');
            }

            // Cek apakah kolom nama_barang sudah ada
            if (!Schema::hasColumn('cash_in_out', 'nama_barang')) {
                $table->string('nama_barang')->nullable()->after('type_id');
            }

            // Cek apakah kolom deksripsi sudah ada
            if (!Schema::hasColumn('cash_in_out', 'deksripsi')) {
                $table->text('deksripsi')->nullable()->after('keterangan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_in_out', function (Blueprint $table) {
            // Hapus kolom jika ada
            if (Schema::hasColumn('cash_in_out', 'keterangan')) {
                $table->dropColumn('keterangan');
            }

            if (Schema::hasColumn('cash_in_out', 'nama_barang')) {
                $table->dropColumn('nama_barang');
            }

            if (Schema::hasColumn('cash_in_out', 'deksripsi')) {
                $table->dropColumn('deksripsi');
            }
        });
    }
};
