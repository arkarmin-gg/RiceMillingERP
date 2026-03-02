<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Party\FilterPartyRequest;
use App\Http\Requests\Party\StorePartyRequest;
use App\Http\Requests\Party\UpdatePartyRequest;
use App\Models\Party;
use App\Models\StockBalance;
use App\Support\QuantityConverter;
use Illuminate\Http\JsonResponse;

class PartyController extends Controller
{
    public function getDispatchableParties(): JsonResponse
    {
        $parties = Party::query()
            ->where('type', 'MERCHANT')
            ->whereHas('stockBalances', function ($query) {
                $query->where('quantity', '>', 0);
            })
            ->with(['stockBalances' => function ($query) {
                $query->where('quantity', '>', 0)->with('item');
            }])
            ->get();

        $data = $parties->map(function (Party $party) {
            return [
                'id' => $party->id,
                'full_name' => $party->full_name,
                'phone' => $party->phone,
                'type' => $party->type,
                'dispatchable_items' => $party->stockBalances->map(function (StockBalance $balance) {
                    $bagsData = QuantityConverter::poundsToBags($balance->quantity, 108);

                    return [
                        'item_id' => $balance->item_id,
                        'item_name' => $balance->item ? $balance->item->name : null,
                        'quantity' => $balance->quantity,
                        'bags' => $bagsData['bags'],
                        'loose_lb' => $bagsData['loose_lb'],
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'data' => $data,
            'message' => 'Dispatchable parties retrieved successfully',
        ]);
    }

    public function index(FilterPartyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $search = $validated['search'] ?? null;
        $type = $validated['type'] ?? null;
        $getAll = array_key_exists('get_all', $validated) && $validated['get_all'] !== null
            ? (bool) $validated['get_all']
            : false;

        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 10);

        $query = Party::query()->orderByDesc('created_at');

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'ILIKE', "%{$search}%")
                    ->orWhere('phone', 'ILIKE', "%{$search}%")
                    ->orWhere('nrc', 'ILIKE', "%{$search}%");
            });
        }

        if ($type !== null && $type !== '') {
            $query->where('type', $type);
        }

        if ($getAll) {
            $parties = $query->get();

            return response()->json([
                'data' => $parties,
                'message' => 'All parties retrieved successfully',
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
            'message' => 'Parties retrieved successfully',
        ]);
    }

    public function store(StorePartyRequest $request): JsonResponse
    {
        $data = $request->validated();

        $party = Party::create($data);

        return response()->json([
            'data' => $party,
            'message' => 'Party created successfully',
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $party = Party::find($id);

        if (! $party) {
            return response()->json([
                'message' => "Party with ID '{$id}' not found",
            ], 404);
        }

        return response()->json([
            'data' => $party,
            'message' => "Party retrieved by ID {$id} successfully",
        ]);
    }

    public function update(UpdatePartyRequest $request, string $id): JsonResponse
    {
        $party = Party::find($id);

        if (! $party) {
            return response()->json([
                'message' => "Party with ID '{$id}' not found",
            ], 404);
        }

        $data = $request->validated();

        $party->fill($data);
        $party->save();

        return response()->json([
            'data' => $party,
            'message' => 'Party updated successfully',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $party = Party::find($id);

        if (! $party) {
            return response()->json([
                'message' => "Party with ID '{$id}' not found",
            ], 404);
        }

        $party->delete();

        return response()->json([
            'message' => "Party with ID '{$id}' has been successfully deleted",
        ]);
    }
}
