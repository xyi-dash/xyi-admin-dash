<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\ActionLogService;
use App\Services\AdminSessionService;
use App\Services\GameAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private GameAccountService $gameAccountService,
        private ActionLogService $actionLog,
        private AdminSessionService $adminSession
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $account = $this->gameAccountService->authenticate(
            $validated['server'],
            $validated['nickname'],
            $validated['password']
        );

        if (! $account) {
            return response()->json([
                'message' => 'Invalid credentials',
                'errors' => ['nickname' => ['reimu checked the shrine records. you are not welcome.']],
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
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->adminSession->lockAll($user);

        $this->actionLog->logLogout(
            $user->game_account_id,
            $user->game_account_name,
            $user->server,
            $request->ip()
        );

        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Sayonara!']);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'account' => [
                'id' => $user->game_account_id,
                'name' => $user->game_account_name,
                'server' => $user->server,
            ],
        ]);
    }

    public function prepareAdminRedirect(Request $request): JsonResponse
    {
        $user = $request->user();

        $adminLevel = $this->gameAccountService->getAdminLevel($user->server, $user->game_account_name);

        if ($adminLevel < 1) {
            return response()->json([
                'error' => 'not_admin',
                'message' => 'reimu says you have no shrine duties here',
            ], 403);
        }

        $token = Str::random(64);

        Cache::put(
            "admin_redirect:{$token}",
            [
                'user_id' => $user->id,
                'server' => $user->server,
                'account_name' => $user->game_account_name,
            ],
            now()->addMinutes(5)
        );

        return response()->json(['token' => $token]);
    }

    public function exchangeToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string|size:64',
        ]);

        $token = $request->input('token');
        $data = Cache::pull("admin_redirect:{$token}");

        if (! $data) {
            return response()->json([
                'error' => 'invalid_token',
                'message' => 'this spell card has expired or never existed',
            ], 401);
        }

        $user = User::find($data['user_id']);

        if (! $user) {
            return response()->json([
                'error' => 'user_not_found',
                'message' => 'the user vanished into gensokyo',
            ], 404);
        }

        $this->adminSession->lockAll($user);
        $user->tokens()->where('name', 'admin-panel')->delete();

        $apiToken = $user->createToken('admin-panel')->plainTextToken;

        $this->actionLog->logAdminPanelAccess(
            $user->game_account_id,
            $user->game_account_name,
            $data['server'],
            $request->ip()
        );

        return response()->json([
            'token' => $apiToken,
            'user' => [
                'id' => $user->game_account_id,
                'name' => $user->game_account_name,
                'server' => $data['server'],
            ],
        ]);
    }

    public function prepareCPRedirect(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = base64_encode(encrypt($user->id.'|'.$user->server.'|'.time()));

        return response()->json([
            'url' => 'https://monser-dm.nl/cp?t='.urlencode($token),
        ]);
    }
}
