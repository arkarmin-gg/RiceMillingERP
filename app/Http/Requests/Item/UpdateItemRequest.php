<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'category' => ['sometimes', 'string', 'in:PADDY,RICE,BROKEN,POINT_BROKEN,BRAN,POINT_BRAN,HUSK,WASTED'],
            'unit' => ['sometimes', 'string', 'in:KG,BAG,TON'],
        ];
    }
}

