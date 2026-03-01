<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Party;
use App\Models\StockBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ItemStockTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_items_with_stock_totals()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create Items
        $item1 = Item::create([
            'name' => 'Rice Item 1',
            'category' => 'RICE',
            'unit' => 'lbs',
        ]);

        $item2 = Item::create([
            'name' => 'Rice Item 2',
            'category' => 'RICE',
            'unit' => 'lbs',
        ]);

        // Create Parties
        $party1 = Party::create([
            'full_name' => 'Merchant One',
            'type' => 'MERCHANT',
            'phone' => '123456789',
        ]);

        $party2 = Party::create([
            'full_name' => 'Merchant Two',
            'type' => 'MERCHANT',
            'phone' => '987654321',
        ]);

        // Add Stock for Item 1 (Total: 108 + 216 = 324 lbs = 3 bags, 0 loose)
        StockBalance::create([
            'owner_id' => $party1->id,
            'item_id' => $item1->id,
            'quantity' => 108,
        ]);

        StockBalance::create([
            'owner_id' => $party2->id,
            'item_id' => $item1->id,
            'quantity' => 216,
        ]);

        // Add Stock for Item 2 (Total: 120 lbs = 1 bag, 12 loose)
        StockBalance::create([
            'owner_id' => $party1->id,
            'item_id' => $item2->id,
            'quantity' => 120,
        ]);

        $response = $this->getJson('/api/v1/items/with-stock');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Check Item 1 totals
        $response->assertJsonFragment([
            'id' => $item1->id,
            'name' => 'Rice Item 1',
            'total_quantity' => 324,
            'total_bags' => 3,
            'total_loose_lb' => 0,
        ]);

        // Check Item 2 totals
        $response->assertJsonFragment([
            'id' => $item2->id,
            'name' => 'Rice Item 2',
            'total_quantity' => 120,
            'total_bags' => 1,
            'total_loose_lb' => 12,
        ]);
    }

    public function test_returns_zero_for_items_without_stock()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $item = Item::create([
            'name' => 'Empty Item',
            'category' => 'RICE',
            'unit' => 'lbs',
        ]);

        $response = $this->getJson('/api/v1/items/with-stock');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $item->id,
                'total_quantity' => 0,
                'total_bags' => 0,
                'total_loose_lb' => 0,
            ]);
    }
}
