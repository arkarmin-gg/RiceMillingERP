<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model
{
    use HasFactory, SoftDeletes, HasUuids, LogsActivity;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'full_name',
        'type',
        'phone',
        'address',
        'nrc',
    ];

    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class, 'owner_id');
    }

    public function getActivityDescription(string $action): string
    {
        return ucfirst(strtolower($action)) . " Party ({$this->full_name})";
    }
}
