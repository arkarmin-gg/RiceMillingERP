<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    use HasFactory;

    protected $table = 'stock_balances';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = null;

    protected $keyType = 'string';

    protected $fillable = [
        'owner_id',
        'item_id',
        'location_id',
        'quantity',
    ];

    public function owner()
    {
        return $this->belongsTo(Party::class, 'owner_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}

