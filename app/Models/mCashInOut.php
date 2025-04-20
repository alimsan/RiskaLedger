<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class mCashInOut extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'cash_in_out';
    protected $primaryKey = 'id';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $guarded = [];

    protected $fillable = [
        'tenant_id',
        'type_id',
        'nama_barang',
        'waktu',
        'nilai',
        'keterangan',
        'deksripsi',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'waktu' => 'datetime',
        'nilai' => 'float',
    ];

    public function type()
    {
        return $this->belongsTo(CashInOutType::class, 'type_id');
    }

    /**
     * Get the tenant that owns the transaction.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Accessor untuk mendapatkan kode tipe
    public function getTypeCodeAttribute()
    {
        if (is_object($this->type) && method_exists($this->type, 'getAttribute')) {
            return $this->type->code;
        } elseif (isset($this->attributes['type']) && is_string($this->attributes['type'])) {
            return $this->attributes['type'];
        }

        return null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('cash_in_out')
            ->setDescriptionForEvent(function (string $eventName) {
                return "{$eventName} cash_in_out";
            });
    }
}
