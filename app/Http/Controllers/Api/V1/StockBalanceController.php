<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockBalance\FilterStockBalanceRequest;
use App\Models\StockBalance;
use App\Support\QuantityConverter;
use Illuminate\Http\JsonResponse;

class StockBalanceController extends Controller
{
    public function index(FilterStockBalanceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $ownerId = $validated['owner_id'] ?? null;
        $itemId = $validated['item_id'] ?? null;
        $getAll = array_key_exists('get_all', $validated) && $validated['get_all'] !== null
            ? (bool) $validated['get_all']
            : false;
        $itemCategory = $validated['item_category'] ?? null;

        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 10);

        $query = StockBalance::query()
            ->with(['owner', 'item'])
            ->orderBy('owner_id')
            ->orderBy('item_id');

        if ($ownerId !== null && $ownerId !== '') {
            $query->where('owner_id', $ownerId);
        }

        if ($itemId !== null && $itemId !== '') {
            $query->where('item_id', $itemId);
        }

        if ($itemCategory !== null && $itemCategory !== '') {
            $query->whereHas('item', function ($q) use ($itemCategory) {
                $q->where('category', $itemCategory);
            });
        }

        if ($getAll) {
            $balances = $query->get();

            $data = $balances->map(function (StockBalance $balance) {
                $bagsData = QuantityConverter::poundsToBags($balance->quantity, 108);

                return [
                    'owner_id' => $balance->owner_id,
                    'owner_name' => $balance->owner ? $balance->owner->full_name : null,
                    'item_id' => $balance->item_id,
                    'item_name' => $balance->item ? $balance->item->name : null,
                    'item_category' => $balance->item ? $balance->item->category : null,
                    'unit' => $balance->item ? $balance->item->unit : null,
                    'quantity' => $balance->quantity,
                    'bags' => $bagsData['bags'],
                    'loose_lb' => $bagsData['loose_lb'],
                ];
            })->values();

            return response()->json([
                'data' => $data,
                'message' => 'All stock balances retrieved successfully',
            ]);
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(function (StockBalance $balance) {
            $bagsData = QuantityConverter::poundsToBags($balance->quantity, 108);

            return [
                'owner_id' => $balance->owner_id,
                'owner_name' => $balance->owner ? $balance->owner->full_name : null,
                'item_id' => $balance->item_id,
                'item_name' => $balance->item ? $balance->item->name : null,
                'item_category' => $balance->item ? $balance->item->category : null,
                'unit' => $balance->item ? $balance->item->unit : null,
                'quantity' => $balance->quantity,
                'bags' => $bagsData['bags'],
                'loose_lb' => $bagsData['loose_lb'],
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'pagination' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
            ],
            'message' => 'Stock balances retrieved successfully',
        ]);
    }
}
