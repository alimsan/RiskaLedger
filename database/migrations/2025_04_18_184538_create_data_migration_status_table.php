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
        Schema::create('data_migration_status', function (Blueprint $table) {
            $table->id();
            $table->string('migration_name')->unique();
            $table->boolean('is_completed')->default(false);
            $table->text('notes')->nullable();
            $table->integer('processed_records')->default(0);
            $table->integer('total_records')->default(0);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_migration_status');
    }
};
