<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'category' => ['required', 'string', 'in:PADDY,RICE,BROKEN,POINT_BROKEN,BRAN,POINT_BRAN,HUSK,WASTED'],
            'unit' => ['required', 'string', 'in:KG,BAG,TON'],
        ];
    }
}

