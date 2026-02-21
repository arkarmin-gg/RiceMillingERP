<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Dispatch extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'merchant_id',
        'dispatch_date',
        'description',
    ];

    public function merchant()
    {
        return $this->belongsTo(Party::class, 'merchant_id');
    }

    public function items()
    {
        return $this->hasMany(DispatchItem::class, 'dispatch_id');
    }

    protected static function booted(): void
    {
        static::creating(function (Dispatch $dispatch): void {
            if (! $dispatch->dispatch_number) {
                $dispatch->dispatch_number = Str::upper(
                    'DP-' . now()->format('YmdHis') . '-' . Str::random(4)
                );
            }
        });
    }
}
