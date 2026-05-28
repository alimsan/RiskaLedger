<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionItems extends Model
{
    protected $table = 'transaction_items';
    protected $guarded = [];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function piutang()
    {
        return $this->belongsTo(Piutang::class, 'transaction_id', 'id');
    }
}
