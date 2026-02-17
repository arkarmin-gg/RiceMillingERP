<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'email' => ['nullable', 'email'],
            'full_name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'password' => ['sometimes', 'string', 'min:8'],
            'phone' => [
                'sometimes',
                'string',
                Rule::unique('users', 'phone')->ignore($userId, 'id'),
            ],
            'date_of_birth' => ['sometimes', 'nullable', 'string'],
            'gender' => ['sometimes', 'nullable', 'string', 'in:male,female'],
            'user_type' => ['sometimes', 'string', 'in:owner,manager,staff'],
            'is_banned' => ['sometimes', 'string'],
            'profile_image_url' => ['sometimes', 'nullable', 'string'],
            'profile_image' => ['sometimes', 'nullable', 'file', 'image', 'max:10240'],
        ];
    }
}
