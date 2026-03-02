<?php

namespace App\Models;

use App\Support\QuantityConverter;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOutput extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'batch_id',
        'item_id',
        'quantity',
    ];

    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'batch_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function getActivityDescription(string $action): string
    {
        $this->loadMissing(['item', 'batch']);
        $itemName = $this->item ? $this->item->name : 'Unknown Item';
        $batchNumber = $this->batch ? $this->batch->batch_number : 'Unknown Batch';

        return ucfirst(strtolower($action)) . " Output ({$itemName}) in Batch ({$batchNumber})";
    }

    public function getActivityProperties(string $action): array
    {
        $this->loadMissing(['item', 'batch']);
        $properties = [
            'item_name' => $this->item ? $this->item->name : null,
            'batch_number' => $this->batch ? $this->batch->batch_number : null,
        ];

        $stockChange = 0;

        if ($action === 'UPDATE') {
            $originalQuantity = $this->getOriginal('quantity');
            $newQuantity = $this->quantity;
            // Output increases stock
            $stockChange = $newQuantity - $originalQuantity;
        } elseif ($action === 'CREATE') {
            // New output increases stock
            $stockChange = $this->quantity;
        } elseif ($action === 'DELETE') {
            // Deleting output reduces stock
            $stockChange = -$this->quantity;
        }

        $properties['stock_impact'] = [
            'change' => $stockChange,
            'unit' => $this->item ? $this->item->unit : null,
        ];

        // Convert stock change to bags/loose_lb
        $bagsData = QuantityConverter::poundsToBags(abs($stockChange), 108);
        $properties['stock_impact']['bags'] = $bagsData['bags'];
        $properties['stock_impact']['loose_lb'] = $bagsData['loose_lb'];

        if ($action === 'UPDATE') {
            $changes = $this->getChanges();
            $original = [];
            foreach ($changes as $key => $value) {
                $original[$key] = $this->getOriginal($key);
            }

            if (array_key_exists('quantity', $changes)) {
                $oldQty = $this->getOriginal('quantity');
                $newQty = $changes['quantity'];

                $oldBags = QuantityConverter::poundsToBags($oldQty, 108);
                $newBags = QuantityConverter::poundsToBags($newQty, 108);

                $original['bags'] = $oldBags['bags'];
                $original['loose_lb'] = $oldBags['loose_lb'];

                $changes['bags'] = $newBags['bags'];
                $changes['loose_lb'] = $newBags['loose_lb'];
            }

            $properties['old'] = $original;
            $properties['new'] = $changes;
        } elseif ($action === 'CREATE') {
            $attributes = $this->getAttributes();
            $bagsData = QuantityConverter::poundsToBags($this->quantity, 108);

            $attributes['bags'] = $bagsData['bags'];
            $attributes['loose_lb'] = $bagsData['loose_lb'];

            $properties['new'] = $attributes;
        }

        return $properties;
    }
}
