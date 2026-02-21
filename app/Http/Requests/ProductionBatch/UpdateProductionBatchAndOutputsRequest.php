<?php

namespace App\Http\Requests\ProductionBatch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductionBatchAndOutputsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'production_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'outputs' => ['sometimes', 'array', 'min:1'],
            'outputs.*.id' => ['required_with:outputs', 'uuid', 'exists:production_outputs,id'],
            'outputs.*.bags' => ['required_with:outputs.*.id', 'integer', 'min:0'],
            'outputs.*.loose_lb' => ['required_with:outputs.*.id', 'integer', 'min:0'],
        ];
    }
}
