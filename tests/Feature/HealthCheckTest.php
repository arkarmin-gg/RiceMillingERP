<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_endpoint_returns_ok_when_database_is_connected()
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'message' => 'Service is healthy',
                'database' => 'connected',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'database',
                'timestamp',
            ]);
    }
}
