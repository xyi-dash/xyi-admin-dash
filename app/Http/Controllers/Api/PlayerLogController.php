<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GameAccountService;
use App\Services\PlayerLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerLogController extends Controller
{
    public function __construct(
        private PlayerLogService $logService,
        private GameAccountService $gameService
    ) {}

    public function availableServers(Request $request): JsonResponse
    {
        if (! $this->canAccess($request)) {
            return response()->json(['error' => 'level_7_required'], 403);
        }

        $user = $request->user();

        return response()->json([
            'servers' => $this->logService->getAvailableServers($user->game_account_name),
            'current' => $user->server,
        ]);
    }

    public function searchPlayer(Request $request): JsonResponse
    {
        if (! $this->canAccess($request)) {
            return response()->json(['error' => 'level_7_required'], 403);
        }

        $request->validate([
            'nickname' => 'nullable|string|max:24',
            'account_id' => 'nullable|integer',
            'server' => 'nullable|string|in:one,two,three',
        ]);

        if (! $request->nickname && ! $request->account_id) {
            return response()->json(['error' => 'give_me_something_to_work_with'], 400);
        }

        $server = $this->resolveServer($request);
        if (! $server) {
            return response()->json(['error' => 'server_access_denied'], 403);
        }

        return response()->json([
            'data' => $this->logService->searchPlayer(
                $server,
                $request->nickname,
                $request->account_id
            ),
        ]);
    }

    public function getPlayerStats(Request $request, int $accountId): JsonResponse
    {
        if (! $this->canAccess($request)) {
            return response()->json(['error' => 'level_7_required'], 403);
        }

        $request->validate([
            'server' => 'nullable|string|in:one,two,three',
        ]);

        $server = $this->resolveServer($request);
        if (! $server) {
            return response()->json(['error' => 'server_access_denied'], 403);
        }

        $stats = $this->logService->getPlayerStats($server, $accountId);
        if (! $stats) {
            return response()->json(['error' => 'player_not_found'], 404);
        }

        return response()->json(['data' => $stats]);
    }

    public function reputationLogs(Request $request): JsonResponse
    {
        if (! $this->canAccess($request)) {
            return response()->json(['error' => 'level_7_required'], 403);
        }

        $request->validate([
            'from' => 'nullable|string|max:24',
            'to' => 'nullable|string|max:24',
            'limit' => 'nullable|integer|min:1|max:500',
            'server' => 'nullable|string|in:one,two,three',
        ]);

        $server = $this->resolveServer($request);
        if (! $server) {
            return response()->json(['error' => 'server_access_denied'], 403);
        }

        return response()->json([
            'data' => $this->logService->getReputationLogs(
                $server,
                $request->from,
                $request->to,
                (int) $request->input('limit', 100)
            ),
        ]);
    }

    public function nicknameLogs(Request $request): JsonResponse
    {
        if (! $this->canAccess($request)) {
            return response()->json(['error' => 'level_7_required'], 403);
        }

        $request->validate([
            'account_id' => 'nullable|integer',
            'old_nick' => 'nullable|string|max:24',
            'new_nick' => 'nullable|string|max:24',
            'limit' => 'nullable|integer|min:1|max:500',
            'server' => 'nullable|string|in:one,two,three',
        ]);

        $server = $this->resolveServer($request);
        if (! $server) {
            return response()->json(['error' => 'server_access_denied'], 403);
        }

        return response()->json([
            'data' => $this->logService->getNicknameLogs(
                $server,
                $request->account_id,
                $request->old_nick,
                $request->new_nick,
                (int) $request->input('limit', 100)
            ),
        ]);
    }

    public function unbanLogs(Request $request): JsonResponse
    {
        if (! $this->canAccess($request)) {
            return response()->json(['error' => 'level_7_required'], 403);
        }

        $request->validate([
            'player' => 'nullable|string|max:24',
            'limit' => 'nullable|integer|min:1|max:200',
            'server' => 'nullable|string|in:one,two,three',
        ]);

        $server = $this->resolveServer($request);
        if (! $server) {
            return response()->json(['error' => 'server_access_denied'], 403);
        }

        return response()->json([
            'data' => $this->logService->getUnbanLogs(
                $server,
                $request->player,
                (int) $request->input('limit', 50)
            ),
        ]);
    }

    public function permanentBans(Request $request): JsonResponse
    {
        if (! $this->canAccess($request)) {
            return response()->json(['error' => 'level_7_required'], 403);
        }

        $request->validate([
            'player' => 'nullable|string|max:24',
            'admin' => 'nullable|string|max:24',
            'limit' => 'nullable|integer|min:1|max:200',
            'server' => 'nullable|string|in:one,two,three',
        ]);

        $server = $this->resolveServer($request);
        if (! $server) {
            return response()->json(['error' => 'server_access_denied'], 403);
        }

        return response()->json([
            'data' => $this->logService->getPermanentBans(
                $server,
                $request->player,
                $request->admin,
                (int) $request->input('limit', 50)
            ),
        ]);
    }

    public function permanentIPBans(Request $request): JsonResponse
    {
        if (! $this->canAccess($request)) {
            return response()->json(['error' => 'level_7_required'], 403);
        }

        $request->validate([
            'ip' => 'nullable|string|max:45',
            'admin' => 'nullable|string|max:24',
            'limit' => 'nullable|integer|min:1|max:200',
            'server' => 'nullable|string|in:one,two,three',
        ]);

        $server = $this->resolveServer($request);
        if (! $server) {
            return response()->json(['error' => 'server_access_denied'], 403);
        }

        return response()->json([
            'data' => $this->logService->getPermanentIPBans(
                $server,
                $request->ip,
                $request->admin,
                (int) $request->input('limit', 50)
            ),
        ]);
    }

    public function matchmakingStats(Request $request): JsonResponse
    {
        if (! $this->canAccess($request)) {
            return response()->json(['error' => 'level_7_required'], 403);
        }

        $request->validate([
            'player' => 'nullable|string|max:24',
            'limit' => 'nullable|integer|min:1|max:500',
            'server' => 'nullable|string|in:one,two,three',
        ]);

        $server = $this->resolveServer($request);
        if (! $server) {
            return response()->json(['error' => 'server_access_denied'], 403);
        }

        return response()->json([
            'data' => $this->logService->getMatchmakingStats(
                $server,
                $request->player,
                (int) $request->input('limit', 100)
            ),
        ]);
    }

    public function moneyTransferLogs(Request $request): JsonResponse
    {
        if (! $this->canAccess($request)) {
            return response()->json(['error' => 'level_7_required'], 403);
        }

        $request->validate([
            'from_id' => 'nullable|integer',
            'from_name' => 'nullable|string|max:24',
            'to_id' => 'nullable|integer',
            'to_name' => 'nullable|string|max:24',
            'limit' => 'nullable|integer|min:1|max:500',
            'server' => 'nullable|string|in:one,two,three',
        ]);

        $server = $this->resolveServer($request);
        if (! $server) {
            return response()->json(['error' => 'server_access_denied'], 403);
        }

        return response()->json([
            'data' => $this->logService->getMoneyTransferLogs(
                $server,
                $request->from_id,
                $request->from_name,
                $request->to_id,
                $request->to_name,
                (int) $request->input('limit', 100)
            ),
        ]);
    }

    public function accessoryLogs(Request $request): JsonResponse
    {
        if (! $this->canAccess($request)) {
            return response()->json(['error' => 'level_7_required'], 403);
        }

        $request->validate([
            'account_id' => 'nullable|integer',
            'account_name' => 'nullable|string|max:24',
            'accessory' => 'nullable|string|max:50',
            'limit' => 'nullable|integer|min:1|max:500',
            'server' => 'nullable|string|in:one,two,three',
        ]);

        $server = $this->resolveServer($request);
        if (! $server) {
            return response()->json(['error' => 'server_access_denied'], 403);
        }

        return response()->json([
            'data' => $this->logService->getAccessoryLogs(
                $server,
                $request->account_id,
                $request->account_name,
                $request->accessory,
                (int) $request->input('limit', 100)
            ),
        ]);
    }

    public function updateBanReason(Request $request, int $banId): JsonResponse
    {
        if (! $this->canAccess($request)) {
            return response()->json(['error' => 'level_7_required'], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:255',
            'server' => 'nullable|string|in:one,two,three',
        ]);

        $server = $this->resolveServer($request);
        if (! $server) {
            return response()->json(['error' => 'server_access_denied'], 403);
        }

        $ban = $this->logService->getBanById($server, $banId);
        if (! $ban) {
            return response()->json(['error' => 'ban_evaporated_into_thin_air'], 404);
        }

        $updated = $this->logService->updateBanReason($server, $banId, $request->reason);
        if (! $updated) {
            return response()->json(['error' => 'database_said_no'], 500);
        }

        return response()->json([
            'message' => 'ban reason updated',
            'data' => [
                'id' => $banId,
                'name' => $ban['name'],
                'reason' => $request->reason,
            ],
        ]);
    }

    // 14 methods and they all start with the same check. i am a professional
    private function canAccess(Request $request): bool
    {
        return $request->attributes->get('admin_level') >= 7;
    }

    private function resolveServer(Request $request): ?string
    {
        $user = $request->user();
        $requested = $request->input('server');

        if (! $requested) {
            return $user->server;
        }

        return $this->logService->hasAccessToServer($user->game_account_name, $requested)
            ? $requested
            : null;
    }
}
