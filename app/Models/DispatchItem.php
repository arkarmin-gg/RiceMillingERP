<?php

namespace App\Models;

use App\Support\QuantityConverter;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispatchItem extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'dispatch_id',
        'item_id',
        'quantity',
    ];

    public function dispatch()
    {
        return $this->belongsTo(Dispatch::class, 'dispatch_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function getActivityDescription(string $action): string
    {
        $this->loadMissing(['item', 'dispatch']);
        $itemName = $this->item ? $this->item->name : 'Unknown Item';
        $dispatchNumber = $this->dispatch ? $this->dispatch->dispatch_number : 'Unknown Dispatch';

        return ucfirst(strtolower($action)) . " Item ({$itemName}) in Dispatch ({$dispatchNumber})";
    }

    public function getActivityProperties(string $action): array
    {
        $this->loadMissing(['item', 'dispatch']);
        $properties = [
            'item_name' => $this->item ? $this->item->name : null,
            'dispatch_number' => $this->dispatch ? $this->dispatch->dispatch_number : null,
        ];

        $stockChange = 0;

        if ($action === 'UPDATE') {
            $originalQuantity = $this->getOriginal('quantity');
            $newQuantity = $this->quantity;
            // Dispatch reduces stock.
            // If quantity increases (new > old), stock decreases more (negative change).
            // If quantity decreases (new < old), stock increases (positive change).
            $stockChange = - ($newQuantity - $originalQuantity);
        } elseif ($action === 'CREATE') {
            // New dispatch reduces stock
            $stockChange = -$this->quantity;
        } elseif ($action === 'DELETE') {
            // Deleting dispatch returns stock
            $stockChange = $this->quantity;
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
