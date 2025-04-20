<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'tenant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the tenant that the user belongs to.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if the user is a superadmin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is an owner.
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Check if the user is an operator.
     */
    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    /**
     * Check if the user is an administrator (superadmin or admin).
     */
    public function isAdministrator(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    /**
     * Check if the user can access tenant data.
     */
    public function canAccessTenant(?int $tenantId = null): bool
    {
        // Superadmin and admin can access any tenant
        if ($this->isAdministrator()) {
            return true;
        }

        // If no specific tenant is provided, check if user has a tenant
        if ($tenantId === null) {
            return $this->tenant_id !== null;
        }

        // For regular users, they can only access their own tenant
        return $this->tenant_id === $tenantId;
    }
}
