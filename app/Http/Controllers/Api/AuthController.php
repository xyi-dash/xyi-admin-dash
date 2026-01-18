<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActionLogService;
use App\Services\GameAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private GameAccountService $gameAccountService,
        private ActionLogService $actionLog
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $account = $this->gameAccountService->authenticate(
            $validated['server'],
            $validated['nickname'],
            $validated['password']
        );

        if (!$account) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => ['nickname' => ['Account not found or password incorrect']]
            ], 422);
        }

        $token = $this->gameAccountService->createSessionToken($account, $validated['server']);

        $this->actionLog->logLogin(
            $account->ID,
            $account->Name,
            $validated['server'],
            $request->ip()
        );

        return response()->json([
            'token' => $token,
            'account' => [
                'id' => $account->ID,
                'name' => $account->Name,
                'server' => $validated['server'],
            ]
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->actionLog->logLogout(
            $user->game_account_id,
            $user->game_account_name,
            $user->server,
            $request->ip()
        );

        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'account' => [
                'id' => $user->game_account_id,
                'name' => $user->game_account_name,
                'server' => $user->server,
            ]
        ]);
    }
}
