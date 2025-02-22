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
            $table->enum('type', [
                'B_BAKU',
                'PERALATAN',
                'BAND',
                'LISTRIK',
                'GAS',
                'REFUND',
                'KASBON',
                'OWNER',
                'COMPLIMENT',
                'BPJS',
                'QRIS',
                'TUNAI',
                'PAJAK',
                'TAX',
                'GAJI',
                'GAJI_C_PIRING'
            ])->default('B_BAKU')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_in_out', function (Blueprint $table) {
            //
        });
    }
};
