<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\XenForoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BlacklistController extends Controller
{
    public function __construct(
        private XenForoService $xenForoService
    ) {}

    public function store(Request $request): JsonResponse
    {
        Log::info('Blacklist request received', [
            'admin_level' => $request->attributes->get('admin_level'),
            'data' => $request->all(),
        ]);

        if ($request->attributes->get('admin_level') < 7) {
            Log::warning('Blacklist access denied - insufficient level', [
                'admin_level' => $request->attributes->get('admin_level'),
            ]);
            return response()->json(['error' => 'level_7_required'], 403);
        }

        $validated = $request->validate([
            'nickname' => 'required|string|max:24',
            'account_id' => 'required|integer',
            'server' => 'required|string|in:one,two,three',
            'reason' => 'required|string|max:500',
            'last_ip' => 'nullable|string|max:45',
            'reg_ip' => 'nullable|string|max:45',
            'last_hash' => 'nullable|string|max:255',
            'vk_link' => 'nullable|string|max:255',
            'forum_account' => 'nullable|string|max:100',
            'discord_login' => 'nullable|string|max:100',
            'screenshot' => 'nullable|string|max:255',
            'proofs' => 'nullable|string|max:500',
        ]);

        Log::info('Blacklist validation passed', ['validated' => $validated]);

        $result = $this->xenForoService->addToBlacklist($validated);

        if ($result['success']) {
            Log::info('Blacklist entry added successfully', ['post_id' => $result['post_id']]);
            return response()->json([
                'message' => $result['message'],
                'post_id' => $result['post_id'],
            ]);
        }

        Log::error('Blacklist entry failed', ['error' => $result['error']]);
        return response()->json([
            'error' => $result['error'],
        ], 500);
    }
}
