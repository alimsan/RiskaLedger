<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cash_in_out_types', function (Blueprint $table) {
            // Hapus unique constraint pada kolom code
            $table->dropUnique(['code']);

            // Tambahkan unique constraint yang baru untuk kombinasi code dan tenant_id
            $table->unique(['code', 'tenant_id'], 'cash_in_out_types_code_tenant_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_in_out_types', function (Blueprint $table) {
            // Hapus unique constraint kombinasi
            $table->dropUnique('cash_in_out_types_code_tenant_id_unique');

            // Kembalikan unique constraint ke kolom code saja
            $table->unique(['code']);
        });
    }
};
