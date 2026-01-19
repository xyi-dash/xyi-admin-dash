<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminManageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_name' => 'required|string|max:24',
            'action' => 'required|string|in:warn,unwarn,promote,demote,remove,reset_password,confirm,give_ga,remove_ga',
            'reason' => 'required_unless:action,reset_password,confirm|string|max:255',
        ];
    }
}
