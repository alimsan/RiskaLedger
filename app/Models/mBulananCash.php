<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class mBulananCash extends Model
{
    protected $table = 'bulanan_cash';
    protected $primaryKey = 'id';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $guarded =[];
}
