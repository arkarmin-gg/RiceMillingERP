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

use App\Models\Location;
use App\Models\ProductionBatch;
use App\Models\ProductionInput;
use App\Models\ProductionOutput;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_production_input_creation_and_update()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $party = Party::create([
            'full_name' => 'Merchant Two',
            'type' => 'MERCHANT',
            'phone' => '987654321',
        ]);

        $item = Item::create([
            'name' => 'Paddy',
            'category' => 'PADDY',
            'unit' => 'lbs',
        ]);

        $location = Location::create([
            'name' => 'Warehouse A',
            'type' => 'STORAGE',
        ]);

        $batch = ProductionBatch::create([
            'merchant_id' => $party->id,
            'production_date' => now(),
            'status' => 'PENDING',
        ]);

        $input = ProductionInput::create([
            'batch_id' => $batch->id,
            'item_id' => $item->id,
            'quantity' => 500,
        ]);

        // Assert Creation Log
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'CREATE',
            'subject_type' => ProductionInput::class,
            'subject_id' => $input->id,
        ]);

        // Update Quantity
        $input->update([
            'quantity' => 600,
        ]);

        $log = ActivityLog::where('action', 'UPDATE')
            ->where('subject_id', $input->id)
            ->orderByDesc('created_at')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(500, $log->properties['old']['quantity']);
        $this->assertEquals(600, $log->properties['new']['quantity']);
    }

    public function test_logs_party_creation()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $party = Party::create([
            'full_name' => 'Test Merchant',
            'type' => 'MERCHANT',
            'phone' => '123456789',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'CREATE',
            'subject_type' => Party::class,
            'subject_id' => $party->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_logs_party_update_with_changes()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $party = Party::create([
            'full_name' => 'Old Name',
            'type' => 'MERCHANT',
            'phone' => '111111111',
        ]);

        $party->update([
            'full_name' => 'New Name',
        ]);

        $log = ActivityLog::where('action', 'UPDATE')
            ->where('subject_id', $party->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($user->id, $log->user_id);

        $properties = $log->properties;
        $this->assertEquals('Old Name', $properties['old']['full_name']);
        $this->assertEquals('New Name', $properties['new']['full_name']);
    }

    public function test_logs_party_deletion()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $party = Party::create([
            'full_name' => 'To Delete',
            'type' => 'MERCHANT',
            'phone' => '222222222',
        ]);

        $party->delete();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'DELETE',
            'subject_type' => Party::class,
            'subject_id' => $party->id,
        ]);
    }

    public function test_logs_dispatch_item_quantity_change()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $party = Party::create([
            'full_name' => 'Merchant One',
            'type' => 'MERCHANT',
            'phone' => '123456789',
        ]);

        $item = Item::create([
            'name' => 'Test Rice',
            'category' => 'RICE',
            'unit' => 'lbs',
        ]);

        $dispatch = Dispatch::create([
            'merchant_id' => $party->id,
            'dispatch_date' => now(),
            'description' => 'Test Dispatch',
        ]);

        $dispatchItem = DispatchItem::create([
            'dispatch_id' => $dispatch->id,
            'item_id' => $item->id,
            'quantity' => 100,
        ]);

        // Assert Creation Log
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'CREATE',
            'subject_type' => DispatchItem::class,
            'subject_id' => $dispatchItem->id,
        ]);

        // Update Quantity
        $dispatchItem->update([
            'quantity' => 200,
        ]);

        $log = ActivityLog::where('action', 'UPDATE')
            ->where('subject_id', $dispatchItem->id)
            ->orderByDesc('created_at')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(100, $log->properties['old']['quantity']);
        $this->assertEquals(200, $log->properties['new']['quantity']);
    }
}
