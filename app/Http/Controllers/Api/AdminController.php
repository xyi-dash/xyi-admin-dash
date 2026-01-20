<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ResolvesServer;
use App\Services\GameAccountService;
use App\Services\ActionLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use ResolvesServer;

    public function __construct(
        private GameAccountService $gameAccountService,
        private ActionLogService $actionLogService
    ) {}

    public function auth(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);
        
        $user = $request->user();
        
        $isValid = $this->gameAccountService->verifyAdminPassword(
            $user->server,
            $user->game_account_name,
            $request->password
        );

        if (!$isValid) {
            return response()->json(['error' => 'Invalid admin password'], 401);
        }

        $this->actionLogService->logAdminAuth(
            $user->game_account_id,
            $user->game_account_name,
            $user->server,
            $request->ip()
        );

        return response()->json(['success' => true]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $server = $this->resolveServer($request);
        
        if (!$server) {
            return response()->json(['error' => 'hakurei barrier sealed this server'], 403);
        }

        $myLevel = $this->getAdminLevelOnServer($request, $server);
        $myAdmin = $this->gameAccountService->getAdminByName($server, $user->game_account_name);
        $isGA = $myAdmin && ($myAdmin->GA ?? 0) == 1;

        $admins = $this->gameAccountService->getAdminList($server, $myLevel, $isGA);

        return response()->json([
            'admins' => $admins,
            'my_level' => $myLevel,
            'total' => count($admins),
            'online' => collect($admins)->where('is_online', true)->count(),
        ]);
    }

    public function show(Request $request, int $adminId): JsonResponse
    {
        $user = $request->user();
        $server = $this->resolveServer($request);
        
        if (!$server) {
            return response()->json(['error' => 'hakurei barrier sealed this server'], 403);
        }

        $myLevel = $this->getAdminLevelOnServer($request, $server);
        $admin = $this->gameAccountService->getAdminById($server, $adminId);

        if (!$admin) {
            return response()->json(['error' => 'admin vanished into the gap'], 404);
        }

        if ($admin->Adm >= $myLevel && $admin->Name !== $user->game_account_name) {
            return response()->json(['error' => 'yukari says no peeking at higher levels'], 403);
        }

        return response()->json([
            'admin' => $this->formatAdminData($admin),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $server = $this->resolveServer($request);
        
        if (!$server) {
            return response()->json(['error' => 'hakurei barrier sealed this server'], 403);
        }

        $admin = $this->gameAccountService->getAdminByName($server, $user->game_account_name);

        if (!$admin) {
            return response()->json(['error' => 'you are not an admin on this server lil blud'], 404);
        }

        return response()->json([
            'admin' => $this->formatAdminData($admin, true),
        ]);
    }

    private function formatAdminData(object $admin, bool $full = false): array
    {
        $data = [
            'id' => $admin->ID,
            'name' => $admin->Name,
            'level' => $admin->Adm,
            'is_ga' => ($admin->GA ?? 0) == 1,
            'warnings' => $admin->Preds ?? 0,
            'appointed_by' => $admin->Kem ?? null,
            'appointed_date' => $admin->Date ?? null,
            'last_online' => $admin->online ?? null,
            'is_online' => ($admin->online2 ?? 0) == 1,
            'reputation' => [
                'up' => $admin->rep_up ?? 0,
                'down' => $admin->rep_down ?? 0,
            ],
            'playtime' => [
                'today' => $this->formatSeconds($admin->Segodnya ?? 0),
                'yesterday' => $this->formatSeconds($admin->Vchera ?? 0),
                'day_before' => $this->formatSeconds($admin->Pozavchera ?? 0),
                'week' => $this->formatSeconds($admin->NOnline ?? 0),
                'three_days_total' => $this->formatSeconds(
                    ($admin->Segodnya ?? 0) + ($admin->Vchera ?? 0) + ($admin->Pozavchera ?? 0)
                ),
            ],
        ];

        if ($full) {
            $data['stats'] = [
                'hours_played' => intval(($admin->OTime ?? 0) / 3600),
                'punishments_given' => $admin->ONakaz ?? 0,
                'reports_answered' => $admin->OOtvet ?? 0,
            ];
            $data['is_confirmed'] = ($admin->admgive ?? 0) == 0;
        }

        return $data;
    }

    private function formatSeconds(int $seconds): string
    {
        $hours = intval($seconds / 3600);
        $mins = intval(($seconds % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $mins);
    }
}

