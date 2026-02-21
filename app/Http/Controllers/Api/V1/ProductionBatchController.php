<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductionBatch\FilterProductionBatchRequest;
use App\Http\Requests\ProductionBatch\StoreProductionBatchAndOutputsRequest;
use App\Http\Requests\ProductionBatch\UpdateProductionBatchAndOutputsRequest;
use App\Models\ProductionBatch;
use App\Models\ProductionOutput;
use App\Models\StockBalance;
use App\Support\QuantityConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductionBatchController extends Controller
{
    public function indexBatchAndOutputs(FilterProductionBatchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $merchantId = $validated['merchant_id'] ?? null;
        $status = $validated['status'] ?? null;
        $fromDate = $validated['from_date'] ?? null;
        $toDate = $validated['to_date'] ?? null;
        $getAll = array_key_exists('get_all', $validated) && $validated['get_all'] !== null
            ? (bool) $validated['get_all']
            : false;

        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 10);

        $query = ProductionBatch::query()
            ->with(['outputs.item'])
            ->orderByDesc('production_date');

        if ($merchantId !== null && $merchantId !== '') {
            $query->where('merchant_id', $merchantId);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($fromDate !== null) {
            $query->whereDate('production_date', '>=', $fromDate);
        }

        if ($toDate !== null) {
            $query->whereDate('production_date', '<=', $toDate);
        }

        if ($getAll) {
            $batches = $query->get();

            $data = $batches->map(function (ProductionBatch $batch) {
                return [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'merchant_id' => $batch->merchant_id,
                    'production_date' => $batch->production_date,
                    'status' => $batch->status,
                    'outputs' => $batch->outputs->map(function (ProductionOutput $output) {
                        $bagsData = QuantityConverter::poundsToBags($output->quantity, 108);

                        return [
                            'id' => $output->id,
                            'batch_id' => $output->batch_id,
                            'item_id' => $output->item_id,
                            'item_name' => $output->item ? $output->item->name : null,
                            'quantity' => $output->quantity,
                            'bags' => $bagsData['bags'],
                            'loose_lb' => $bagsData['loose_lb'],
                        ];
                    })->values(),
                ];
            })->values();

            return response()->json([
                'data' => $data,
                'message' => 'All production batches with outputs retrieved successfully',
            ]);
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(function (ProductionBatch $batch) {
            return [
                'id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'merchant_id' => $batch->merchant_id,
                'production_date' => $batch->production_date,
                'status' => $batch->status,
                'outputs' => $batch->outputs->map(function (ProductionOutput $output) {
                    $bagsData = QuantityConverter::poundsToBags($output->quantity, 108);

                    return [
                        'id' => $output->id,
                        'batch_id' => $output->batch_id,
                        'item_id' => $output->item_id,
                        'item_name' => $output->item ? $output->item->name : null,
                        'quantity' => $output->quantity,
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
            'message' => 'Production batches with outputs retrieved successfully',
        ]);
    }

    public function showBatchAndOutputs(string $id): JsonResponse
    {
        $batch = ProductionBatch::query()
            ->with(['outputs.item'])
            ->find($id);

        if (! $batch) {
            return response()->json([
                'message' => "Production batch with ID '{$id}' not found",
            ], 404);
        }

        $data = [
            'id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'merchant_id' => $batch->merchant_id,
            'production_date' => $batch->production_date,
            'status' => $batch->status,
            'outputs' => $batch->outputs->map(function (ProductionOutput $output) {
                $bagsData = QuantityConverter::poundsToBags($output->quantity, 108);

                return [
                    'id' => $output->id,
                    'batch_id' => $output->batch_id,
                    'item_id' => $output->item_id,
                    'item_name' => $output->item ? $output->item->name : null,
                    'quantity' => $output->quantity,
                    'bags' => $bagsData['bags'],
                    'loose_lb' => $bagsData['loose_lb'],
                ];
            })->values(),
        ];

        return response()->json([
            'data' => $data,
            'message' => "Production batch with outputs retrieved by ID {$id} successfully",
        ]);
    }

    public function updateBatchAndOutputs(UpdateProductionBatchAndOutputsRequest $request, string $id): JsonResponse
    {
        $batch = ProductionBatch::query()->find($id);

        if (! $batch) {
            return response()->json([
                'message' => "Production batch with ID '{$id}' not found",
            ], 404);
        }

        $data = $request->validated();

        $batchUpdate = [];

        if (array_key_exists('production_date', $data)) {
            $batchUpdate['production_date'] = $data['production_date'];
        }

        if (array_key_exists('status', $data)) {
            $batchUpdate['status'] = $data['status'];
        }

        $outputsPayload = $data['outputs'] ?? null;

        $existingOutputs = null;

        if ($outputsPayload !== null) {
            $outputIds = collect($outputsPayload)->pluck('id')->all();

            $existingOutputs = ProductionOutput::query()
                ->where('batch_id', $batch->id)
                ->whereIn('id', $outputIds)
                ->get()
                ->keyBy('id');

            if (count($existingOutputs) !== count($outputIds)) {
                return response()->json([
                    'message' => 'One or more outputs do not belong to the specified production batch',
                ], 422);
            }
        }

        DB::transaction(function () use ($batch, $batchUpdate, $outputsPayload, $existingOutputs) {
            if ($batchUpdate !== []) {
                $batch->update($batchUpdate);
            }

            if ($outputsPayload !== null) {
                foreach ($outputsPayload as $outputData) {

                    $existing = $existingOutputs[$outputData['id']];

                    $quantityLb = QuantityConverter::bagsToPounds(
                        (int) $outputData['bags'],
                        (int) $outputData['loose_lb'],
                        108
                    );

                    $updateData = [
                        'quantity' => $quantityLb,
                    ];

                    $delta = $quantityLb - $existing->quantity;

                    if ($delta !== 0) {
                        $this->adjustStockBalance(
                            $batch->merchant_id,
                            $existing->item_id,
                            $delta
                        );
                    }

                    ProductionOutput::query()
                        ->whereKey($outputData['id'])
                        ->update($updateData);
                }
            }
        });

        $batch = ProductionBatch::query()
            ->with(['outputs.item'])
            ->find($id);

        $responseData = [
            'id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'merchant_id' => $batch->merchant_id,
            'production_date' => $batch->production_date,
            'status' => $batch->status,
            'outputs' => $batch->outputs->map(function (ProductionOutput $output) {
                $bagsData = QuantityConverter::poundsToBags($output->quantity, 108);

                return [
                    'id' => $output->id,
                    'batch_id' => $output->batch_id,
                    'item_id' => $output->item_id,
                    'item_name' => $output->item ? $output->item->name : null,
                    'quantity' => $output->quantity,
                    'bags' => $bagsData['bags'],
                    'loose_lb' => $bagsData['loose_lb'],
                ];
            })->values(),
        ];

        return response()->json([
            'data' => $responseData,
            'message' => "Production batch and outputs updated by ID {$id} successfully",
        ]);
    }

    public function storeBatchAndOutputs(StoreProductionBatchAndOutputsRequest $request): JsonResponse
    {
        $data = $request->validated();

        $batchData = [
            'merchant_id' => $data['merchant_id'],
            'production_date' => $data['production_date'],
            'status' => $data['status'],
        ];

        $outputs = $data['outputs'];

        $result = DB::transaction(function () use ($batchData, $outputs) {
            $batch = ProductionBatch::create($batchData);

            $createdOutputs = [];

            foreach ($outputs as $outputData) {
                $quantityLb = QuantityConverter::bagsToPounds(
                    (int) $outputData['bags'],
                    (int) $outputData['loose_lb'],
                    108
                );

                $this->adjustStockBalance(
                    $batch->merchant_id,
                    $outputData['item_id'],
                    $quantityLb
                );

                $createdOutputs[] = ProductionOutput::create([
                    'batch_id' => $batch->id,
                    'item_id' => $outputData['item_id'],
                    'quantity' => $quantityLb,
                ]);
            }

            return [
                'batch' => $batch->fresh(),
                'outputs' => $createdOutputs,
            ];
        });

        return response()->json([
            'data' => $result,
            'message' => 'Production batch and outputs stored successfully',
        ], 201);
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
