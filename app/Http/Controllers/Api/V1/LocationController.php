<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Location\FilterLocationRequest;
use App\Http\Requests\Location\StoreLocationRequest;
use App\Http\Requests\Location\UpdateLocationRequest;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function index(FilterLocationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $search = $validated['search'] ?? null;
        $type = $validated['type'] ?? null;
        $getAll = array_key_exists('get_all', $validated) && $validated['get_all'] !== null
            ? (bool) $validated['get_all']
            : false;

        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 10);

        $query = Location::query()->orderByDesc('created_at');

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%");
            });
        }

        if ($type !== null && $type !== '') {
            $query->where('type', $type);
        }

        if ($getAll) {
            $locations = $query->get();

            return response()->json([
                'data' => $locations,
                'message' => 'All locations retrieved successfully',
            ]);
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);
        $items = $paginator->items();

        return response()->json([
            'data' => $items,
            'pagination' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
            ],
            'message' => 'Locations retrieved successfully',
        ]);
    }

    public function store(StoreLocationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Check if location with the same name already exists
        $existingLocationName = Location::query()->where('name', $data['name'])->where('type', $data['type'])->first();

        if ($existingLocationName) {
            return response()->json([
                'message' => "Location with name '{$data['name']}' already exists for type '{$data['type']}'",
            ], 409);
        }

        $location = Location::create($data);

        return response()->json([
            'data' => $location,
            'message' => 'Location created successfully',
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $location = Location::query()->find($id);

        if (! $location) {
            return response()->json([
                'message' => "Location with ID '{$id}' not found",
            ], 404);
        }

        return response()->json([
            'data' => $location,
            'message' => "Location retrieved by ID {$id} successfully",
        ]);
    }

    public function update(UpdateLocationRequest $request, string $id): JsonResponse
    {
        $location = Location::find($id);

        if (! $location) {
            return response()->json([
                'message' => "Location with ID '{$id}' not found",
            ], 404);
        }

        $data = $request->validated();

        $location->fill($data);
        $location->save();

        return response()->json([
            'data' => $location,
            'message' => 'Location updated successfully',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $location = Location::query()->find($id);

        if (! $location) {
            return response()->json([
                'message' => "Location with ID '{$id}' not found",
            ], 404);
        }

        // Soft delete
        $location->delete();

        return response()->json([
            'message' => "Location with ID '{$id}' has been successfully deleted",
        ]);
    }
}
