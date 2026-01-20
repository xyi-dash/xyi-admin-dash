<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\ResolvesServer;
use App\Http\Requests\AdminManageRequest;
use App\Models\ActionLog;
use App\Services\AdminManagementService;
use App\Services\GameAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminManagementController extends Controller
{
    use ResolvesServer;

    public function __construct(
        private AdminManagementService $mgmtService,
        private GameAccountService $gameService
    ) {}

    public function execute(AdminManageRequest $request): JsonResponse
    {
        $user = $request->user();
        $server = $this->resolveServer($request);
        
        if (!$server) {
            return response()->json(['error' => 'boundary_of_fantasy_and_reality_blocked'], 403);
        }

        $myLevel = $this->getAdminLevelOnServer($request, $server);
        $myAdmin = $this->gameService->getAdminByName($server, $user->game_account_name);
        $isGA = $myAdmin && ($myAdmin->GA ?? 0) == 1;

        $targetName = $request->target_name;
        $action = $request->action;
        $reason = $request->reason ?? '';

        $targetAdmin = $this->gameService->getAdminByName($server, $targetName);

        if (!$targetAdmin) {
            return response()->json(['error' => 'admin_not_found'], 404);
        }

        $availableActions = $this->mgmtService->getAvailableActions($myLevel, $isGA, $targetAdmin->Adm);

        if (!in_array($action, $availableActions)) {
            return response()->json(['error' => 'action_not_allowed'], 403);
        }

        $result = match($action) {
            'warn' => $this->mgmtService->warn(
                $server, $targetName, $reason,
                $myAdmin->ID, $user->game_account_name, $request->ip()
            ),
            'unwarn' => $this->mgmtService->unwarn(
                $server, $targetName, $reason,
                $myAdmin->ID, $user->game_account_name, $request->ip()
            ),
            'promote' => $this->mgmtService->promote(
                $server, $targetName, $reason,
                $myAdmin->ID, $user->game_account_name, $myLevel, $request->ip()
            ),
            'demote' => $this->mgmtService->demote(
                $server, $targetName, $reason,
                $myAdmin->ID, $user->game_account_name, $myLevel, $request->ip()
            ),
            'remove' => $this->mgmtService->remove(
                $server, $targetName, $reason,
                $myAdmin->ID, $user->game_account_name, $myLevel, $request->ip()
            ),
            'reset_password' => $this->mgmtService->resetPassword(
                $server, $targetName,
                $myAdmin->ID, $user->game_account_name, $myLevel, $request->ip()
            ),
            'confirm' => $this->mgmtService->confirm(
                $server, $targetName,
                $myAdmin->ID, $user->game_account_name, $request->ip()
            ),
            'give_ga' => $this->mgmtService->giveGA(
                $server, $targetName, $reason,
                $myAdmin->ID, $user->game_account_name, $request->ip()
            ),
            'remove_ga' => $this->mgmtService->removeGA(
                $server, $targetName, $reason,
                $myAdmin->ID, $user->game_account_name, $request->ip()
            ),
            default => ['success' => false, 'error' => 'spell_card_rules_violation'],
        };

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 400);
        }

        $updatedAdmin = $this->gameService->getAdminByName($server, $targetName);

        return response()->json([
            'success' => true,
            'admin' => $updatedAdmin ? $this->formatAdmin($updatedAdmin) : null,
        ]);
    }

    public function history(Request $request, string $adminName): JsonResponse
    {
        $server = $this->resolveServer($request, 7);
        
        if (!$server) {
            return response()->json(['error' => 'need 7+ for time travel'], 403);
        }

        $logs = ActionLog::where(function ($q) use ($adminName, $server) {
            $q->where('actor_name', $adminName)->where('actor_server', $server);
        })->orWhere(function ($q) use ($adminName, $server) {
            $q->where('target_name', $adminName)->where('target_server', $server);
        })
        ->orderByDesc('created_at')
        ->limit(100)
        ->get()
        ->map(fn($log) => [
            'id' => $log->id,
            'action' => $log->action_type,
            'actor' => $log->actor_name,
            'target' => $log->target_name,
            'details' => $log->details,
            'ip' => $log->ip_address,
            'date' => $log->created_at->format('Y-m-d H:i:s'),
        ]);

        return response()->json(['logs' => $logs]);
    }

    public function availableActions(Request $request, string $adminName): JsonResponse
    {
        $user = $request->user();
        $server = $this->resolveServer($request);
        
        if (!$server) {
            return response()->json(['error' => 'sakuya stopped time and blocked you'], 403);
        }

        $myLevel = $this->getAdminLevelOnServer($request, $server);
        $myAdmin = $this->gameService->getAdminByName($server, $user->game_account_name);
        $isGA = $myAdmin && ($myAdmin->GA ?? 0) == 1;

        $targetAdmin = $this->gameService->getAdminByName($server, $adminName);

        if (!$targetAdmin) {
            return response()->json(['error' => 'admin_spirited_away'], 404);
        }

        $actions = $this->mgmtService->getAvailableActions($myLevel, $isGA, $targetAdmin->Adm);

        return response()->json([
            'actions' => $actions,
            'admin' => $this->formatAdmin($targetAdmin),
        ]);
    }

    private function formatAdmin(object $admin): array
    {
        return [
            'id' => $admin->ID,
            'name' => $admin->Name,
            'level' => $admin->Adm,
            'is_ga' => ($admin->GA ?? 0) == 1,
            'warnings' => $admin->Preds ?? 0,
            'needs_confirm' => ($admin->admgive ?? 0) == 1,
            'appointed_by' => $admin->Kem ?? null,
            'appointed_date' => $admin->Date ?? null,
        ];
    }
}
