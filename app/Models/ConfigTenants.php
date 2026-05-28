<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigTenants extends Model
{
    protected $table = 'config_tenants';

    protected $fillable = [
        'name',
        'status',
        'tenant_id',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
