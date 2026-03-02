<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Dispatch;
use App\Models\DispatchItem;
use App\Models\Item;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityLogEnhancementTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_contain_enhanced_description_and_properties_for_dispatch_items()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $merchant = Party::create([
            'full_name' => 'Merchant Test',
            'type' => 'MERCHANT',
            'phone' => '123123123',
        ]);

        $dispatch = Dispatch::create([
            'merchant_id' => $merchant->id,
            'dispatch_date' => now(),
        ]);

        $item = Item::create([
            'name' => 'Rice Bag 50kg',
            'category' => 'RICE',
            'unit' => 'bag',
        ]);

        // Create Item
        $dispatchItem = DispatchItem::create([
            'dispatch_id' => $dispatch->id,
            'item_id' => $item->id,
            'quantity' => 10,
        ]);

        // Assert Creation Log
        $log = ActivityLog::where('subject_type', DispatchItem::class)
            ->where('subject_id', $dispatchItem->id)
            ->where('action', 'CREATE')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Rice Bag 50kg', $log->description);
        $this->assertStringContainsString($dispatch->dispatch_number, $log->description);
        $this->assertEquals('Rice Bag 50kg', $log->properties['item_name']);
        $this->assertEquals($dispatch->dispatch_number, $log->properties['dispatch_number']);

        // Update Item
        $dispatchItem->update(['quantity' => 20]);

        // Assert Update Log
        $updateLog = ActivityLog::where('subject_type', DispatchItem::class)
            ->where('subject_id', $dispatchItem->id)
            ->where('action', 'UPDATE')
            ->first();

        $this->assertNotNull($updateLog);
        $this->assertStringContainsString('Rice Bag 50kg', $updateLog->description);
        $this->assertStringContainsString($dispatch->dispatch_number, $updateLog->description);
        $this->assertEquals('Rice Bag 50kg', $updateLog->properties['item_name']);
    }

    public function test_logs_contain_enhanced_description_for_party()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $party = Party::create([
            'full_name' => 'New Merchant',
            'type' => 'MERCHANT',
            'phone' => '999999',
        ]);

        $log = ActivityLog::where('subject_type', Party::class)
            ->where('subject_id', $party->id)
            ->where('action', 'CREATE')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('New Merchant', $log->description);
    }
}
