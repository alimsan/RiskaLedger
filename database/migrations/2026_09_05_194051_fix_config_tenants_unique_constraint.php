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
        Schema::table('config_tenants', function (Blueprint $table) {
            $table->dropUnique('config_tenants_name_unique');
            $table->unique(['tenant_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_tenants', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'name']);
            $table->unique('name');
        });
    }
};
