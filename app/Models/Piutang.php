<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Piutang extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'vendor_id',
        'type_id',
        'qty',
        'total_utang',
        'bukti_resi',
        'waktu',
        'tenant_id',
        'lunas',
        'image_pelunasan',
    ];

    protected $casts = [
        'waktu' => 'datetime',
        'qty' => 'integer',
        'total_utang' => 'decimal:2',
        'lunas' => 'boolean',
    ];

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function($piutang) {
            // Menghapus semua transaction_items terkait
            $piutang->transactionItems()->delete();
        });
    }

    /**
     * Get the tenant that owns the piutang.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the vendor that owns the piutang.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the type that owns the piutang.
     */
    public function type()
    {
        return $this->belongsTo(CashInOutType::class, 'type_id');
    }

    /**
     * Get the transaction items for the piutang.
     */
    public function transactionItems()
    {
        return $this->hasMany(TransactionItems::class, 'transaction_id', 'id')->where('transaction_type', 'piutang');
    }

    /**
     * Get the bukti_resi URL attribute.
     */
    public function getBuktiResiUrlAttribute()
    {
        if ($this->bukti_resi) {
            return url('storage/' . $this->bukti_resi);
        }

        return null;
    }

    /**
     * Get the image_pelunasan URL attribute.
     */
    public function getImagePelunasanUrlAttribute()
    {
        if ($this->image_pelunasan) {
            return url('storage/' . $this->image_pelunasan);
        }

        return null;
    }

    /**
     * Get the activity log options for the model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('piutangs')
            ->setDescriptionForEvent(function (string $eventName) {
                return "Piutang untuk vendor {$this->vendor->nama_vendor} telah {$eventName}";
            });
    }
}
