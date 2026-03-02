<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityLog\FilterActivityLogRequest;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;

class ActivityLogController extends Controller
{
    public function index(FilterActivityLogRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $action = $validated['action'] ?? null;
        $subjectType = $validated['subject_type'] ?? null;
        $subjectId = $validated['subject_id'] ?? null;
        $userId = $validated['user_id'] ?? null;
        $fromDate = $validated['from_date'] ?? null;
        $toDate = $validated['to_date'] ?? null;

        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 10);

        $query = ActivityLog::query()
            ->with(['user', 'admin'])
            ->orderByDesc('created_at');

        if ($action) {
            $query->where('action', $action);
        }

        if ($subjectType) {
            $query->where('subject_type', 'like', "%{$subjectType}%");
        }

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
            ],
            'message' => 'Activity logs retrieved successfully',
        ]);
    }
}
