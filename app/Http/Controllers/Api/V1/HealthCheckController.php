<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class HealthCheckController extends Controller
{
    /**
     * Check the health of the application.
     */
    public function index(): JsonResponse
    {
        try {
            // Check database connection
            DB::connection()->getPdo();
            
            return response()->json([
                'status' => 'ok',
                'message' => 'Service is healthy',
                'database' => 'connected',
                'timestamp' => now()->toIso8601String(),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service is unhealthy',
                'database' => 'disconnected',
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}
