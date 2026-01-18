<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Services\ActionLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
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
}

