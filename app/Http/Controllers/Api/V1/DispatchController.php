<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dispatch\FilterDispatchRequest;
use App\Http\Requests\Dispatch\StoreDispatchAndItemsRequest;
use App\Http\Requests\Dispatch\UpdateDispatchAndItemsRequest;
use App\Models\Dispatch;
use App\Models\DispatchItem;
use App\Models\StockBalance;
use App\Support\QuantityConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DispatchController extends Controller
{
    public function indexDispatchAndItems(FilterDispatchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $merchantId = $validated['merchant_id'] ?? null;
        $fromDate = $validated['from_date'] ?? null;
        $toDate = $validated['to_date'] ?? null;
        $getAll = array_key_exists('get_all', $validated) && $validated['get_all'] !== null
            ? (bool) $validated['get_all']
            : false;

        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 10);

        $query = Dispatch::query()
            ->with(['merchant', 'items.item'])
            ->orderByDesc('dispatch_date');

        if ($merchantId !== null && $merchantId !== '') {
            $query->where('merchant_id', $merchantId);
        }

        if ($fromDate !== null) {
            $query->whereDate('dispatch_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->whereDate('dispatch_date', '<=', $toDate);
        }

        if ($getAll) {
            $dispatches = $query->get();

            $data = $dispatches->map(function (Dispatch $dispatch) {
                return [
                    'id' => $dispatch->id,
                    'dispatch_number' => $dispatch->dispatch_number,
                    'merchant_id' => $dispatch->merchant_id,
                    'merchant_name' => $dispatch->merchant ? $dispatch->merchant->full_name : null,
                    'dispatch_date' => $dispatch->dispatch_date,
                    'description' => $dispatch->description,
                    'items' => $dispatch->items->map(function (DispatchItem $item) {
                        $bagsData = QuantityConverter::poundsToBags($item->quantity, 108);

                        return [
                            'id' => $item->id,
                            'dispatch_id' => $item->dispatch_id,
                            'item_id' => $item->item_id,
                            'item_name' => $item->item ? $item->item->name : null,
                            'quantity' => $item->quantity,
                            'bags' => $bagsData['bags'],
                            'loose_lb' => $bagsData['loose_lb'],
                        ];
                    })->values(),
                ];
            })->values();

            return response()->json([
                'data' => $data,
                'message' => 'All dispatches with items retrieved successfully',
            ]);
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(function (Dispatch $dispatch) {
            return [
                'id' => $dispatch->id,
                'dispatch_number' => $dispatch->dispatch_number,
                'merchant_id' => $dispatch->merchant_id,
                'merchant_name' => $dispatch->merchant ? $dispatch->merchant->full_name : null,
                'dispatch_date' => $dispatch->dispatch_date,
                'description' => $dispatch->description,
                'items' => $dispatch->items->map(function (DispatchItem $item) {
                    $bagsData = QuantityConverter::poundsToBags($item->quantity, 108);

                    return [
                        'id' => $item->id,
                        'dispatch_id' => $item->dispatch_id,
                        'item_id' => $item->item_id,
                        'item_name' => $item->item ? $item->item->name : null,
                        'quantity' => $item->quantity,
                        'bags' => $bagsData['bags'],
                        'loose_lb' => $bagsData['loose_lb'],
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'pagination' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
            ],
            'message' => 'Dispatches with items retrieved successfully',
        ]);
    }

    public function showDispatchAndItems(string $id): JsonResponse
    {
        $dispatch = Dispatch::query()
            ->with(['merchant', 'items.item'])
            ->find($id);

        if (! $dispatch) {
            return response()->json([
                'message' => "Dispatch with ID '{$id}' not found",
            ], 404);
        }

        $data = [
            'id' => $dispatch->id,
            'dispatch_number' => $dispatch->dispatch_number,
            'merchant_id' => $dispatch->merchant_id,
            'merchant_name' => $dispatch->merchant ? $dispatch->merchant->full_name : null,
            'dispatch_date' => $dispatch->dispatch_date,
            'description' => $dispatch->description,
            'items' => $dispatch->items->map(function (DispatchItem $item) {
                $bagsData = QuantityConverter::poundsToBags($item->quantity, 108);

                return [
                    'id' => $item->id,
                    'dispatch_id' => $item->dispatch_id,
                    'item_id' => $item->item_id,
                    'item_name' => $item->item ? $item->item->name : null,
                    'quantity' => $item->quantity,
                    'bags' => $bagsData['bags'],
                    'loose_lb' => $bagsData['loose_lb'],
                ];
            })->values(),
        ];

        return response()->json([
            'data' => $data,
            'message' => "Dispatch with items retrieved by ID {$id} successfully",
        ]);
    }

    public function storeDispatchAndItems(StoreDispatchAndItemsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $dispatchData = [
            'merchant_id' => $data['merchant_id'],
            'dispatch_date' => $data['dispatch_date'],
            'description' => $data['description'] ?? null,
        ];

        $items = $data['items'];

        $result = DB::transaction(function () use ($dispatchData, $items) {
            $dispatch = Dispatch::create($dispatchData);

            $createdItems = [];

            foreach ($items as $itemData) {
                $quantityLb = QuantityConverter::bagsToPounds(
                    (int) $itemData['bags'],
                    (int) $itemData['loose_lb'],
                    108
                );

                $createdItem = DispatchItem::create([
                    'dispatch_id' => $dispatch->id,
                    'item_id' => $itemData['item_id'],
                    'quantity' => $quantityLb,
                ]);

                $this->adjustStockBalance(
                    $dispatch->merchant_id,
                    $itemData['item_id'],
                    -$quantityLb
                );

                $createdItems[] = $createdItem;
            }

            return [
                'dispatch' => $dispatch->fresh(['merchant']),
                'items' => $createdItems,
            ];
        });

        return response()->json([
            'data' => $result,
            'message' => 'Dispatch and items stored successfully',
        ], 201);
    }

    public function updateDispatchAndItems(UpdateDispatchAndItemsRequest $request, string $id): JsonResponse
    {
        $dispatch = Dispatch::query()->find($id);

        if (! $dispatch) {
            return response()->json([
                'message' => "Dispatch with ID '{$id}' not found",
            ], 404);
        }

        $data = $request->validated();

        $dispatchUpdate = [];

        if (array_key_exists('dispatch_date', $data)) {
            $dispatchUpdate['dispatch_date'] = $data['dispatch_date'];
        }

        if (array_key_exists('description', $data)) {
            $dispatchUpdate['description'] = $data['description'];
        }

        $itemsPayload = $data['items'] ?? null;

        $existingItems = null;

        if ($itemsPayload !== null) {
            $itemIds = collect($itemsPayload)->pluck('id')->all();

            $existingItems = DispatchItem::query()
                ->where('dispatch_id', $dispatch->id)
                ->whereIn('id', $itemIds)
                ->get()
                ->keyBy('id');

            if (count($existingItems) !== count($itemIds)) {
                return response()->json([
                    'message' => 'One or more items do not belong to the specified dispatch',
                ], 422);
            }
        }

        DB::transaction(function () use ($dispatch, $dispatchUpdate, $itemsPayload, $existingItems) {
            if ($dispatchUpdate !== []) {
                $dispatch->update($dispatchUpdate);
            }

            if ($itemsPayload !== null) {
                foreach ($itemsPayload as $itemData) {
                    $existing = $existingItems[$itemData['id']];

                    $quantityLb = QuantityConverter::bagsToPounds(
                        (int) $itemData['bags'],
                        (int) $itemData['loose_lb'],
                        108
                    );

                    $updateData = [
                        'quantity' => $quantityLb,
                    ];

                    $delta = $quantityLb - $existing->quantity;

                    if ($delta !== 0) {
                        $this->adjustStockBalance(
                            $dispatch->merchant_id,
                            $existing->item_id,
                            -$delta
                        );
                    }

                    DispatchItem::query()
                        ->whereKey($itemData['id'])
                        ->update($updateData);
                }
            }
        });

        $dispatch = Dispatch::query()
            ->with(['merchant', 'items.item'])
            ->find($id);

        $responseData = [
            'id' => $dispatch->id,
            'dispatch_number' => $dispatch->dispatch_number,
            'merchant_id' => $dispatch->merchant_id,
            'merchant_name' => $dispatch->merchant ? $dispatch->merchant->full_name : null,
            'dispatch_date' => $dispatch->dispatch_date,
            'description' => $dispatch->description,
            'items' => $dispatch->items->map(function (DispatchItem $item) {
                $bagsData = QuantityConverter::poundsToBags($item->quantity, 108);

                return [
                    'id' => $item->id,
                    'dispatch_id' => $item->dispatch_id,
                    'item_id' => $item->item_id,
                    'item_name' => $item->item ? $item->item->name : null,
                    'quantity' => $item->quantity,
                    'bags' => $bagsData['bags'],
                    'loose_lb' => $bagsData['loose_lb'],
                ];
            })->values(),
        ];

        return response()->json([
            'data' => $responseData,
            'message' => "Dispatch and items updated by ID {$id} successfully",
        ]);
    }

    private function adjustStockBalance(string $ownerId, string $itemId, int $deltaQuantity): void
    {
        $balanceQuery = StockBalance::query()
            ->where('owner_id', $ownerId)
            ->where('item_id', $itemId);

        $balance = $balanceQuery->lockForUpdate()->first();

        if (! $balance) {
            StockBalance::query()->create([
                'owner_id' => $ownerId,
                'item_id' => $itemId,
                'quantity' => $deltaQuantity,
            ]);

            return;
        }

        $newQuantity = $balance->quantity + $deltaQuantity;

        $balanceQuery->update([
            'quantity' => $newQuantity,
        ]);
    }
}
