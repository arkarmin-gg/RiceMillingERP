<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Dispatch;
use App\Models\DispatchItem;
use App\Models\Item;
use App\Models\Party;
use App\Models\ProductionBatch;
use App\Models\ProductionInput;
use App\Models\ProductionOutput;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityLogStockImpactTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_dispatch_item_stock_impact()
    {
        $item = Item::create(['name' => 'Rice Bag', 'unit' => 'bag', 'category' => 'RICE']);
        $merchant = Party::create(['full_name' => 'Merchant A', 'type' => 'MERCHANT', 'phone' => '09123456789']);
        $dispatch = Dispatch::create(['merchant_id' => $merchant->id, 'dispatch_date' => now()]);

        $dispatchItem = DispatchItem::create([
            'dispatch_id' => $dispatch->id,
            'item_id' => $item->id,
            'quantity' => 10,
        ]);

        $log = ActivityLog::where('subject_type', DispatchItem::class)
            ->where('subject_id', $dispatchItem->id)
            ->where('action', 'CREATE')
            ->first();

        $this->assertEquals(-10, $log->properties['stock_impact']['change']);
        $this->assertEquals('bag', $log->properties['stock_impact']['unit']);
        $this->assertEquals(0, $log->properties['stock_impact']['bags']);
        $this->assertEquals(10, $log->properties['stock_impact']['loose_lb']);

        // Test UPDATE (Increase quantity)
        $dispatchItem->update(['quantity' => 15]);

        $log = ActivityLog::where('subject_type', DispatchItem::class)
            ->where('subject_id', $dispatchItem->id)
            ->where('action', 'UPDATE')
            ->orderByDesc('created_at')
            ->first();

        // Quantity went from 10 to 15 (increase of 5 in dispatch).
        // Stock impact is negative (dispatch reduces stock).
        // Change = -(15 - 10) = -5
        $this->assertEquals(-5, $log->properties['stock_impact']['change']);

        // Test UPDATE (Decrease quantity)
        // Sleep to ensure timestamp difference
        sleep(1);
        $dispatchItem->update(['quantity' => 5]);

        $log = ActivityLog::where('subject_type', DispatchItem::class)
            ->where('subject_id', $dispatchItem->id)
            ->where('action', 'UPDATE')
            ->orderByDesc('created_at')
            ->first();

        // Quantity went from 15 to 5 (decrease of 10 in dispatch).
        // Stock impact is positive (stock returned).
        // Change = -(5 - 15) = -(-10) = 10
        $this->assertEquals(10, $log->properties['stock_impact']['change']);
    }

    public function test_logs_production_input_stock_impact()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $merchant = Party::create([
            'full_name' => 'Merchant Test',
            'type' => 'MERCHANT',
            'phone' => '123123123',
        ]);

        $batch = ProductionBatch::create([
            'merchant_id' => $merchant->id,
            'production_date' => now(),
            'status' => 'DRAFT',
        ]);

        $item = Item::create(['name' => 'Paddy', 'unit' => 'kg', 'category' => 'PADDY']);

        // Test CREATE
        $input = ProductionInput::create([
            'batch_id' => $batch->id,
            'item_id' => $item->id,
            'quantity' => 100,
        ]);

        $log = ActivityLog::where('subject_type', ProductionInput::class)
            ->where('subject_id', $input->id)
            ->where('action', 'CREATE')
            ->first();

        // Input usage reduces stock
        $this->assertEquals(-100, $log->properties['stock_impact']['change']);
        $this->assertEquals('kg', $log->properties['stock_impact']['unit']);
        $this->assertEquals(0, $log->properties['stock_impact']['bags']);
        $this->assertEquals(100, $log->properties['stock_impact']['loose_lb']);

        // Test UPDATE
        $input->update(['quantity' => 150]);

        $log = ActivityLog::where('subject_type', ProductionInput::class)
            ->where('subject_id', $input->id)
            ->where('action', 'UPDATE')
            ->first();

        // Usage increased by 50, so stock reduced by 50 more
        $this->assertEquals(-50, $log->properties['stock_impact']['change']);
    }

    public function test_logs_production_output_stock_impact()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $merchant = Party::create([
            'full_name' => 'Merchant Test',
            'type' => 'MERCHANT',
            'phone' => '123123123',
        ]);

        $batch = ProductionBatch::create([
            'merchant_id' => $merchant->id,
            'production_date' => now(),
            'status' => 'DRAFT',
        ]);

        $item = Item::create(['name' => 'Rice', 'unit' => 'bag', 'category' => 'RICE']);

        // Test CREATE
        $output = ProductionOutput::create([
            'batch_id' => $batch->id,
            'item_id' => $item->id,
            'quantity' => 50,
        ]);

        $log = ActivityLog::where('subject_type', ProductionOutput::class)
            ->where('subject_id', $output->id)
            ->where('action', 'CREATE')
            ->first();

        // Output increases stock
        $this->assertEquals(50, $log->properties['stock_impact']['change']);
        $this->assertEquals('bag', $log->properties['stock_impact']['unit']);
        $this->assertEquals(0, $log->properties['stock_impact']['bags']);
        $this->assertEquals(50, $log->properties['stock_impact']['loose_lb']);

        // Test UPDATE
        $output->update(['quantity' => 60]);

        $log = ActivityLog::where('subject_type', ProductionOutput::class)
            ->where('subject_id', $output->id)
            ->where('action', 'UPDATE')
            ->first();

        // Output increased by 10, so stock increased by 10
        $this->assertEquals(10, $log->properties['stock_impact']['change']);
    }

    public function test_logs_dispatch_header_update_structure()
    {
        $merchant = Party::create(['full_name' => 'Merchant B', 'type' => 'MERCHANT', 'phone' => '0987654321']);
        $dispatch = Dispatch::create([
            'merchant_id' => $merchant->id,
            'dispatch_date' => now(),
            'description' => 'Original Description',
        ]);

        // Update dispatch header
        $dispatch->update(['description' => 'Updated Description']);

        $log = ActivityLog::where('subject_type', Dispatch::class)
            ->where('subject_id', $dispatch->id)
            ->where('action', 'UPDATE')
            ->orderByDesc('created_at')
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('merchant_name', $log->properties);
        $this->assertArrayHasKey('dispatch_number', $log->properties);

        // Stock impact should NOT be present on dispatch header updates
        $this->assertArrayNotHasKey('stock_impact', $log->properties);
    }
}
