<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CashInOutType extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $table = 'cash_in_out_types';
    protected $guarded = [];

    public function transactions()
    {
        return $this->hasMany(mCashInOut::class, 'type_id');
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
