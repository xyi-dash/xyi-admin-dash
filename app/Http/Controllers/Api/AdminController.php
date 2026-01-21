<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ResolvesServer;
use App\Models\ControlPanelUser;
use App\Services\AdminSessionService;
use App\Services\GameAccountService;
use App\Services\ActionLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use ResolvesServer;

    public function __construct(
        private GameAccountService $gameAccountService,
        private ActionLogService $actionLogService,
        private AdminSessionService $adminSessionService
    ) {}

    public function auth(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
            'server' => 'nullable|string|in:one,two,three',
        ]);
        
        $user = $request->user();
        $server = $request->input('server', $user->server);

        $admin = $this->gameAccountService->getAdminByName($server, $user->game_account_name);
        
        if (!$admin || ($admin->Adm ?? 0) < 1) {
            return response()->json([
                'error' => 'not_admin',
                'message' => 'you have no power here, gandalf the grey',
            ], 403);
        }

        $isValid = $this->gameAccountService->verifyAdminPassword(
            $server,
            $user->game_account_name,
            $request->password
        );

        if (!$isValid) {
            return response()->json([
                'error' => 'invalid_password',
                'message' => 'wrong spell card',
            ], 401);
        }

        $this->adminSessionService->unlock($user, $server, $request->ip());

        $this->actionLogService->logAdminAuth(
            $user->game_account_id,
            $user->game_account_name,
            $server,
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'server' => $server,
            'admin_level' => $admin->Adm,
            'unlocked_servers' => $this->adminSessionService->getUnlockedServers($user),
        ]);
    }

    public function sessionStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $unlockedServers = $this->adminSessionService->getUnlockedServers($user);
        
        $adminOnServers = [];
        foreach (['one', 'two', 'three'] as $server) {
            $admin = $this->gameAccountService->getAdminByName($server, $user->game_account_name);
            if ($admin && ($admin->Adm ?? 0) >= 1) {
                $adminOnServers[] = [
                    'server' => $server,
                    'level' => $admin->Adm,
                    'is_ga' => ($admin->GA ?? 0) == 1,
                    'unlocked' => collect($unlockedServers)->contains('server', $server),
                ];
            }
        }

        return response()->json([
            'unlocked_servers' => $unlockedServers,
            'admin_on_servers' => $adminOnServers,
            'can_access_cp' => ControlPanelUser::hasAccess($user->game_account_name, $user->server)
                && $this->adminSessionService->hasAnyUnlocked($user),
        ]);
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
        $canViewDetails = $this->gameAccountService->canViewAdminDetails($myLevel, $isGA);

        return response()->json([
            'admins' => $admins,
            'my_level' => $myLevel,
            'can_view_details' => $canViewDetails,
            'total' => count($admins),
            'online' => collect($admins)->where('is_online', true)->count(),
        ]);
    }

    public function show(Request $request, int $adminId): JsonResponse
    {
        $user = $request->user();
        $server = $this->resolveServer($request);
        
        if (!$server) {
            return response()->json(['error' => 'ran_yakumo_is_guarding_this_server'], 403);
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
            return response()->json(['error' => 'server_not_unlocked'], 403);
        }

        $admin = $this->gameAccountService->getAdminByName($server, $user->game_account_name);

        if (!$admin) {
            return response()->json(['error' => 'not_admin_here'], 404);
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
            'is_support' => ($admin->is_support ?? 0) == 1,
            'is_youtuber' => ($admin->is_media ?? 0) == 1,
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
