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
        // Migrate existing user-tenant relationships to pivot table
        $users = DB::table('users')->whereNotNull('tenant_id')->get();
        
        foreach ($users as $user) {
            DB::table('tenant_user')->insert([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Set current_tenant_id to the existing tenant_id
            DB::table('users')
                ->where('id', $user->id)
                ->update(['current_tenant_id' => $user->tenant_id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the pivot table
        DB::table('tenant_user')->truncate();
        
        // Clear current_tenant_id
        DB::table('users')->update(['current_tenant_id' => null]);
    }
};
