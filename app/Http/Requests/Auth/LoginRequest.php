<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'server' => ['required', 'string', 'in:one,two,three'],
            'nickname' => ['required', 'string', 'max:24'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'server.in' => 'Invalid server selected',
            'nickname.max' => 'Nickname too long',
        ];
    }
}

