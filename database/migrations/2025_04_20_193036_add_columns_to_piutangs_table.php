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
        Schema::table('piutangs', function (Blueprint $table) {
            // Tambahkan kolom type_id yang mengacu ke tabel cash_in_out_types
            $table->foreignId('type_id')->nullable()->after('vendor_id')
                ->constrained('cash_in_out_types')
                ->nullOnDelete();

            // Tambahkan kolom lunas sebagai boolean
            $table->boolean('lunas')->default(false)->after('waktu');

            // Tambahkan kolom image_pelunasan
            $table->string('image_pelunasan')->nullable()->after('lunas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('piutangs', function (Blueprint $table) {
            // Hapus kolom-kolom yang ditambahkan
            $table->dropForeign(['type_id']);
            $table->dropColumn('type_id');
            $table->dropColumn('lunas');
            $table->dropColumn('image_pelunasan');
        });
    }
};
