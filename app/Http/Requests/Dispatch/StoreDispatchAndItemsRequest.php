<?php

namespace App\Http\Requests\Dispatch;

use Illuminate\Foundation\Http\FormRequest;

class StoreDispatchAndItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'merchant_id' => ['required', 'uuid', 'exists:parties,id'],
            'dispatch_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'uuid', 'exists:items,id'],
            'items.*.bags' => ['required', 'integer', 'min:0'],
            'items.*.loose_lb' => ['required', 'integer', 'min:0'],
        ];
    }
}
