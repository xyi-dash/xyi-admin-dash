<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Services\ActionLogService;
use App\Services\GameAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function __construct(
        private GameAccountService $gameService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string|max:50',
            'actor_id' => 'nullable|integer|min:1',
            'actor_server' => 'nullable|string|in:one,two,three',
            'target_id' => 'nullable|integer|min:1',
            'target_server' => 'nullable|string|in:one,two,three',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $user = $request->user();

        if (! $this->isAdmin($user)) {
            return response()->json(['error' => 'you_are_not_admin'], 403);
        }

        $query = ActionLog::query()->orderByDesc('created_at');

        if ($type = $request->input('type')) {
            $query->ofType($type);
        }

        if ($request->has('actor_id') && $request->has('actor_server')) {
            $query->byActor($request->input('actor_id'), $request->input('actor_server'));
        }

        if ($request->has('target_id') && $request->has('target_server')) {
            $query->byTarget($request->input('target_id'), $request->input('target_server'));
        }

        $logs = $query->paginate($request->input('per_page', 50));

        return response()->json($logs);
    }

    public function byPerson(Request $request, int $personId, string $server): JsonResponse
    {
        $request->validate([
            'type' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if (! in_array($server, ['one', 'two', 'three'])) {
            return response()->json(['error' => 'invalid_server'], 422);
        }

        $user = $request->user();

        if (! $this->isAdmin($user)) {
            return response()->json(['error' => 'you_are_not_admin'], 403);
        }

        $query = ActionLog::query()
            ->byPerson($personId, $server)
            ->orderByDesc('created_at');

        if ($type = $request->input('type')) {
            $query->ofType($type);
        }

        $logs = $query->paginate($request->input('per_page', 50));

        return response()->json($logs);
    }

    public function types(): JsonResponse
    {
        return response()->json(ActionLogService::getActionTypes());
    }

    private function isAdmin($user): bool
    {
        return $this->gameService->getAdminLevel($user->server, $user->game_account_name) > 0;
    }
}
