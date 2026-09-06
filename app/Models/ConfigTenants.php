<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigTenants extends Model
{
    protected $table = 'config_tenants';

    protected $fillable = [
        'name',
        'status',
        'tenant_id',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Cek apakah konfigurasi tenant aktif untuk tenant tertentu
     */
    public static function isConfigActive(?int $tenantId, string $configName): bool
    {
        if (!$tenantId) {
            return false;
        }

        return static::where('tenant_id', $tenantId)
            ->where('name', $configName)
            ->where('status', true)
            ->exists();
    }

    /**
     * Cek apakah user saat ini adalah operator dengan konfigurasi strict_operator aktif
     */
    public static function isStrictOperator(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return false;
        }

        // Cek apakah user memiliki peran operator (baik dari Spatie roles atau atribut kolom role)
        $isOperator = $user->hasRole('operator') || $user->role === 'operator';
        if (!$isOperator) {
            return false;
        }

        return static::isConfigActive($user->tenant_id, 'strict_operator');
    }
}
