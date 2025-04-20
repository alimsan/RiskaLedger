<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the profit sharings for the tenant.
     */
    public function profitSharings()
    {
        return $this->hasMany(ProfitSharing::class);
    }

    /**
     * Get the default profit sharing for the tenant.
     */
    public function defaultProfitSharing()
    {
        return $this->hasOne(ProfitSharing::class)->where('is_default', true);
    }

    /**
     * Get the transactions for the tenant.
     */
    public function transactions()
    {
        return $this->hasMany(mCashInOut::class);
    }

    /**
     * Get the items for the tenant.
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Get the users for the tenant.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the owner user for the tenant.
     */
    public function owner()
    {
        return $this->hasOne(User::class)->where('role', 'owner');
    }

    /**
     * Get the operator user for the tenant.
     */
    public function operator()
    {
        return $this->hasOne(User::class)->where('role', 'operator');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('tenants')
            ->setDescriptionForEvent(function (string $eventName) {
                return "{$eventName} tenant";
            });
    }
}
