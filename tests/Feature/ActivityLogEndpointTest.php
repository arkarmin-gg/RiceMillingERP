<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityLogEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_activity_logs()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create a party to generate a log
        Party::create([
            'full_name' => 'Log Test Merchant',
            'type' => 'MERCHANT',
            'phone' => '123123123',
        ]);

        $response = $this->getJson('/api/v1/activity-logs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'action',
                        'description',
                        'subject_type',
                        'subject_id',
                        'properties',
                        'created_at',
                    ],
                ],
                'pagination',
            ]);
    }

    public function test_can_filter_activity_logs_by_action()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create logs with different actions
        $party = Party::create([
            'full_name' => 'Create Log',
            'type' => 'MERCHANT',
            'phone' => '111111111',
        ]);

        $party->update(['full_name' => 'Update Log']);

        $response = $this->getJson('/api/v1/activity-logs?action=UPDATE');

        $response->assertStatus(200)
            ->assertJsonFragment(['action' => 'UPDATE'])
            ->assertJsonMissing(['action' => 'CREATE']);
    }

    public function test_can_filter_activity_logs_by_subject_type()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Party::create([
            'full_name' => 'Party Log',
            'type' => 'MERCHANT',
            'phone' => '222222222',
        ]);

        $response = $this->getJson('/api/v1/activity-logs?subject_type=Party');

        $response->assertStatus(200)
            ->assertJsonFragment(['subject_type' => Party::class]);
    }
}
