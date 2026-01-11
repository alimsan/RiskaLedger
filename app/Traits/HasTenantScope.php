<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasTenantScope
{
    /**
     * Get current tenant ID from authenticated user.
     */
    public static function getCurrentTenantId(): ?int
    {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }

        return $user->getCurrentTenantId();
    }

    /**
     * Apply tenant scope to query.
     */
    public static function applyTenantScope(Builder $query): Builder
    {
        $tenantId = static::getCurrentTenantId();
        
        if (!$tenantId) {
            return $query;
        }

        // Check if user is administrator
        $user = auth()->user();
        if ($user && $user->isAdministrator()) {
            return $query;
        }

        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope query to current tenant.
     */
    public function scopeTenant(Builder $query): Builder
    {
        return static::applyTenantScope($query);
    }
}
