<?php

namespace App\Http\Requests\Dispatch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDispatchAndItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dispatch_date' => ['sometimes', 'date'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['required_with:items', 'uuid', 'exists:dispatch_items,id'],
            'items.*.bags' => ['required_with:items.*.id', 'integer', 'min:0'],
            'items.*.loose_lb' => ['required_with:items.*.id', 'integer', 'min:0'],
        ];
    }
}
