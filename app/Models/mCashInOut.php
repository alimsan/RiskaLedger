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
    protected $guarded =[];
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
