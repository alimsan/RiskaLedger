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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['superadmin', 'admin', 'owner', 'operator'])->default('operator')->after('remember_token');
            $table->foreignId('tenant_id')->nullable()->after('role')->constrained('tenants')->nullOnDelete();
            $table->index('role');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['role']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn(['role', 'tenant_id']);
        });
    }
};
