<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TenantUserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        if (!$user->tenant_id || in_array($user->role, ['superadmin', 'admin'])) {
            return;
        }

        $this->validateTenantUserLimit($user);
    }

    /**
     * Handle the User "updating" event.
     */
    public function updating(User $user): void
    {
        // Jika role atau tenant_id berubah, validasi ulang
        if (
            $user->isDirty(['role', 'tenant_id']) &&
            $user->tenant_id &&
            !in_array($user->role, ['superadmin', 'admin'])
        ) {
            $this->validateTenantUserLimit($user);
        }
    }

    /**
     * Validate tenant user limit (1 owner, 1 operator per tenant).
     */
    protected function validateTenantUserLimit(User $user): void
    {
        // Check if tenant already has a user with the same role
        $existingUser = User::where('tenant_id', $user->tenant_id)
            ->where('role', $user->role);

        // Exclude current user if updating
        if ($user->exists) {
            $existingUser->where('id', '!=', $user->id);
        }

        $existingUser = $existingUser->first();

        if ($existingUser) {
            $roleNames = [
                'owner' => 'Owner',
                'operator' => 'Operator',
            ];

            $roleName = $roleNames[$user->role] ?? $user->role;

            throw ValidationException::withMessages([
                'role' => ["Tenant sudah memiliki {$roleName}. Hanya boleh ada 1 {$roleName} per tenant."],
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
