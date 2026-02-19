<?php

namespace App\Http\Requests\Party;

use Illuminate\Foundation\Http\FormRequest;

class StorePartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:2', 'max:100'],
            'type' => ['required', 'string', 'in:FARMER,BROKER,CUSTOMER,MERCHANT'],
            'phone' => ['required', 'string', 'unique:parties,phone'],
            'address' => ['nullable', 'string'],
            'nrc' => ['nullable', 'string'],
        ];
    }
}
