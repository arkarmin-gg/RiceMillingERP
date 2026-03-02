<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductionBatch extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'merchant_id',
        'production_date',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductionBatch $batch): void {
            if (! $batch->batch_number) {
                $batch->batch_number = Str::upper(
                    'PB-' . now()->format('YmdHis') . '-' . Str::random(4)
                );
            }
        });
    }

    public function merchant()
    {
        return $this->belongsTo(Party::class, 'merchant_id');
    }

    public function inputs()
    {
        return $this->hasMany(ProductionInput::class, 'batch_id');
    }

    public function outputs()
    {
        return $this->hasMany(ProductionOutput::class, 'batch_id');
    }

    public function getActivityDescription(string $action): string
    {
        $description = ucfirst(strtolower($action)) . " Production Batch";
        if ($this->batch_number) {
            $description .= " ({$this->batch_number})";
        }
        return $description;
    }
}
