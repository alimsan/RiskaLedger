<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
class ActivityLogs extends Model
{
    use LogsActivity;
    protected $table = 'activity_log';

    public function user(){
        return $this->belongsTo(User::class,' causer_id');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('activityLog');
    }
}
