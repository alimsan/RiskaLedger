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
        Schema::create('cash_in_out', function (Blueprint $table) {
            $table->id();
            $table->string('nama_barang')->nullable();
            $table->enum('type',['B_BAKU','PERALATAN','BAND','LISTRIK','GAS','REFUND','KASBON','OWNER','COMPLIMENT','BPJS','QRIS','TUNAI'])->default('B_BAKU');
            $table->text('deksripsi')->nullable();
            $table->tinyInteger('tipe_cio')->default('2')->comment("no 1 pemasukan no 2 pengeluaran");
            $table->bigInteger('nilai');
            $table->dateTime('waktu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_in_out');
    }
};
