<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email'],
            'full_name' => ['required', 'string', 'min:2', 'max:100'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['required', 'string', 'unique:users,phone'],
            'date_of_birth' => ['nullable', 'string'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'user_type' => ['required', 'string', 'in:owner,manager,staff'],
            'is_banned' => ['sometimes', 'string'],
            'profile_image_url' => ['nullable', 'string'],
            'profile_image' => ['nullable', 'file', 'image', 'max:10240'],
        ];
    }
}
