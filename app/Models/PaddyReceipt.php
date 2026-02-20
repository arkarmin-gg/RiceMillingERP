<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaddyReceipt extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'receipt_number',
        'merchant_id',
        'received_date',
        'description',
    ];

    public function merchant()
    {
        return $this->belongsTo(Party::class, 'merchant_id');
    }

    public function items()
    {
        return $this->hasMany(PaddyReceiptItem::class, 'receipt_id');
    }
}

