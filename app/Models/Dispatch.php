<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Dispatch extends Model
{
    use HasFactory, HasUuids, LogsActivity;

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

    public function getActivityProperties(string $action): array
    {
        $this->loadMissing('merchant');

        $properties = [
            'merchant_name' => $this->merchant ? $this->merchant->full_name : null,
            'dispatch_number' => $this->dispatch_number,
        ];

        return $properties;
    }

    public function getActivityDescription(string $action): string
    {
        $description = ucfirst(strtolower($action)) . " Dispatch";
        if ($this->dispatch_number) {
            $description .= " ({$this->dispatch_number})";
        }
        return $description;
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
