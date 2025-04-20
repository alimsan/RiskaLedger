<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Vendor extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'nama_vendor',
        'tenant_id',
    ];

    /**
     * Get the tenant that owns the vendor.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the piutangs for the vendor.
     */
    public function piutangs()
    {
        return $this->hasMany(Piutang::class);
    }

    /**
     * Get the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('vendors')
            ->setDescriptionForEvent(function (string $eventName) {
                return "Vendor {$this->nama_vendor} telah {$eventName}";
            });
    }
}
