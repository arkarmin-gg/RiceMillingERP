<?php

namespace App\Http\Requests\Party;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $partyId = $this->route('id');

        return [
            'full_name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'type' => ['sometimes', 'string', 'in:FARMER,BROKER,CUSTOMER,MERCHANT'],
            'phone' => [
                'sometimes',
                'string',
                Rule::unique('parties', 'phone')->ignore($partyId, 'id'),
            ],
            'address' => ['sometimes', 'nullable', 'string'],
            'nrc' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
