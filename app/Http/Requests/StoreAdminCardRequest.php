<?php

namespace App\Http\Requests;

use App\Services\GameAccountService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_admin_name' => [
                'required',
                'string',
                'min:1',
                'max:24',
                function ($attribute, $value, $fail) {
                    $server = $this->input('server') ?: $this->user()->server;
                    $gameService = app(GameAccountService::class);
                    $admin = $gameService->getAdminByName($server, $value);
                    
                    if (! $admin) {
                        $fail('The specified administrator does not exist.');
                    }
                },
            ],
            'action_type' => [
                'required',
                'string',
                Rule::in(['warning_add', 'warning_remove', 'permanent_ban']),
            ],
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'evidence' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'target_admin_name.required' => 'Target administrator name is required.',
            'target_admin_name.max' => 'Administrator name cannot exceed 24 characters.',
            'action_type.required' => 'Action type is required.',
            'action_type.in' => 'Invalid action type.',
            'reason.required' => 'Reason is required.',
            'reason.min' => 'Reason must be at least 10 characters.',
            'reason.max' => 'Reason cannot exceed 1000 characters.',
            'evidence.max' => 'Evidence cannot exceed 2000 characters.',
        ];
    }
}
