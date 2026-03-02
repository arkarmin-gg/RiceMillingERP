<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Item\FilterItemRequest;
use App\Http\Requests\Item\StoreItemRequest;
use App\Http\Requests\Item\UpdateItemRequest;
use App\Models\Item;
use App\Support\QuantityConverter;
use Illuminate\Http\JsonResponse;

class ItemController extends Controller
{
    public function getItemsWithStock(): JsonResponse
    {
        $items = Item::query()
            ->withSum('stockBalances', 'quantity')
            ->get();

        $data = $items->map(function (Item $item) {
            $totalQuantity = $item->stock_balances_sum_quantity ?? 0;
            $bagsData = QuantityConverter::poundsToBags($totalQuantity, 108);

            return [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'unit' => $item->unit,
                'total_quantity' => $totalQuantity,
                'total_bags' => $bagsData['bags'],
                'total_loose_lb' => $bagsData['loose_lb'],
            ];
        });

        return response()->json([
            'data' => $data,
            'message' => 'Items with stock totals retrieved successfully',
        ]);
    }

    public function index(FilterItemRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $search = $validated['search'] ?? null;
        $category = $validated['category'] ?? null;
        $unit = $validated['unit'] ?? null;
        $getAll = array_key_exists('get_all', $validated) && $validated['get_all'] !== null
            ? (bool) $validated['get_all']
            : false;

        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 10);

        $query = Item::query()->orderByDesc('created_at');

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%");
            });
        }

        if ($category !== null && $category !== '') {
            $query->where('category', $category);
        }

        if ($unit !== null && $unit !== '') {
            $query->where('unit', $unit);
        }

        if ($getAll) {
            $items = $query->get();

            return response()->json([
                'data' => $items,
                'message' => 'All items retrieved successfully',
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
            'message' => 'Items retrieved successfully',
        ]);
    }

    public function store(StoreItemRequest $request): JsonResponse
    {
        $data = $request->validated();

        $existingItem = Item::query()
            ->where('name', $data['name'])
            ->where('category', $data['category'])
            ->first();

        if ($existingItem) {
            return response()->json([
                'message' => "Item with name '{$data['name']}' already exists for category '{$data['category']}'",
            ], 409);
        }

        $item = Item::create($data);

        return response()->json([
            'data' => $item,
            'message' => 'Item created successfully',
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $item = Item::query()->find($id);

        if (! $item) {
            return response()->json([
                'message' => "Item with ID '{$id}' not found",
            ], 404);
        }

        return response()->json([
            'data' => $item,
            'message' => "Item retrieved by ID {$id} successfully",
        ]);
    }

    public function update(UpdateItemRequest $request, string $id): JsonResponse
    {
        $item = Item::query()->find($id);

        if (! $item) {
            return response()->json([
                'message' => "Item with ID '{$id}' not found",
            ], 404);
        }

        $data = $request->validated();

        $item->update($data);

        $item->refresh();

        return response()->json([
            'data' => $item,
            'message' => 'Item updated successfully',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $item = Item::query()->find($id);

        if (! $item) {
            return response()->json([
                'message' => "Item with ID '{$id}' not found",
            ], 404);
        }

        $item->delete();

        return response()->json([
            'message' => "Item with ID '{$id}' has been successfully deleted",
        ]);
    }
}
