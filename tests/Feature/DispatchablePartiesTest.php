<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Party;
use App\Models\StockBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DispatchablePartiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_dispatchable_parties()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create a party with positive stock balance
        $party1 = Party::create([
            'full_name' => 'Merchant One',
            'type' => 'MERCHANT',
            'phone' => '123456789',
        ]);

        $item1 = Item::create([
            'name' => 'Rice Item 1',
            'category' => 'RICE',
            'unit' => 'lbs',
        ]);

        StockBalance::create([
            'owner_id' => $party1->id,
            'item_id' => $item1->id,
            'quantity' => 1000,
        ]);

        // Create a party with zero stock balance
        $party2 = Party::create([
            'full_name' => 'Merchant Two',
            'type' => 'MERCHANT',
            'phone' => '987654321',
        ]);

        StockBalance::create([
            'owner_id' => $party2->id,
            'item_id' => $item1->id,
            'quantity' => 0,
        ]);

        // Create a party with no stock balance
        $party3 = Party::create([
            'full_name' => 'Merchant Three',
            'type' => 'MERCHANT',
            'phone' => '111111111',
        ]);

        // Create a non-merchant party with stock balance
        $party4 = Party::create([
            'full_name' => 'Farmer One',
            'type' => 'FARMER',
            'phone' => '555555555',
        ]);

        StockBalance::create([
            'owner_id' => $party4->id,
            'item_id' => $item1->id,
            'quantity' => 500,
        ]);

        $response = $this->getJson('/api/v1/parties/dispatchable');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['full_name' => 'Merchant One'])
            ->assertJsonMissing(['full_name' => 'Merchant Two'])
            ->assertJsonMissing(['full_name' => 'Merchant Three'])
            ->assertJsonMissing(['full_name' => 'Farmer One']);

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'full_name',
                    'dispatchable_items' => [
                        '*' => [
                            'item_id',
                            'item_name',
                            'quantity',
                            'bags',
                            'loose_lb',
                        ]
                    ]
                ]
            ]
        ]);
    }
}
