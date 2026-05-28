<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CashInOutType extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'cash_in_out_types';
    protected $guarded = [];

    protected $fillable = [
        'code',
        'name',
        'slug',
        'description',
        'is_income',
        'is_active',
        'show_akhir',
        'sort_order',
        'tenant_id',
    ];

    protected $casts = [
        'is_income' => 'boolean',
        'is_active' => 'boolean',
        'show_akhir' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(mCashInOut::class, 'type_id');
    }

    /**
     * Get the tenant that owns the transaction type.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Aturan validasi untuk tipe transaksi.
     *
     * @return array
     */
    public static function rules($id = null)
    {
        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cash_in_out_types', 'code')
                    ->where(function ($query) {
                        return $query->where('tenant_id', auth()->user()->tenant_id);
                    })
                    ->ignore($id)
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_income' => 'boolean',
            'is_active' => 'boolean',
            'show_akhir' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('cash_in_out_types')
            ->setDescriptionForEvent(function (string $eventName) {
                return "{$eventName} tipe cash in out";
            });
    }
}
