<?php

namespace App\Http\Requests\ProductionBatch;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionBatchAndOutputsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchant_id' => ['required', 'uuid', 'exists:parties,id'],
            'production_date' => ['required', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
            'outputs' => ['required', 'array', 'min:1'],
            'outputs.*.item_id' => ['required', 'uuid', 'exists:items,id'],
            'outputs.*.bags' => ['required', 'integer', 'min:0'],
            'outputs.*.loose_lb' => ['required', 'integer', 'min:0'],
        ];
    }
}
