<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_ledger';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $fillable = [
        'movement_type',
        'reference_id',
        'owner_id',
        'item_id',
        'location_id',
        'quantity',
        'direction',
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

