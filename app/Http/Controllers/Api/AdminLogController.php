<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\ResolvesServer;
use App\Http\Controllers\Controller;
use App\Services\ActionLogService;
use App\Services\AdminLogService;
use App\Services\GameAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    use ResolvesServer;

    public function __construct(
        private AdminLogService $logService,
        private GameAccountService $gameService,
        private ActionLogService $actionLog
    ) {}

    public function adminActions(Request $request): JsonResponse
    {
        $server = $this->resolveServer($request, 6);
        if (! $server) {
            return response()->json(['error' => 'marisa stole your access'], 403);
        }

        if (! $this->canViewLogs($request, $server)) {
            return response()->json(['error' => 'cirno tried but 6+ needed'], 403);
        }

        $myLevel = $this->getAdminLevelOnServer($request, $server);

        return response()->json(
            $this->logService->getAdminActions(
                $server,
                $myLevel,
                (int) $request->query('page', 0),
                $request->query('admin'),
                $request->query('player'),
                $request->query('cmd'),
                $request->query('reason'),
                $request->boolean('with_kills')
            )
        );
    }

    public function warnings(Request $request): JsonResponse
    {
        $server = $this->resolveServer($request, 6);
        if (! $server) {
            return response()->json(['error' => 'marisa stole your access'], 403);
        }

        if (! $this->canViewLogs($request, $server)) {
            return response()->json(['error' => 'cirno tried but 6+ needed'], 403);
        }

        return response()->json([
            'data' => $this->logService->getIssuedWarnings(
                $server,
                $request->query('issued_by'),
                $request->query('issued_to'),
                $request->query('reason')
            ),
        ]);
    }

    public function purchases(Request $request): JsonResponse
    {
        $server = $this->resolveServer($request, 6);
        if (! $server) {
            return response()->json(['error' => 'marisa stole your access'], 403);
        }

        if (! $this->canViewLogs($request, $server)) {
            return response()->json(['error' => 'cirno tried but 6+ needed'], 403);
        }

        return response()->json(
            $this->logService->getPurchases(
                $server,
                (int) $request->query('page', 0),
                $request->query('admin'),
                $request->query('vk'),
                $request->query('type') ? (int) $request->query('type') : null,
                $request->query('level') ? (int) $request->query('level') : null
            )
        );
    }

    public function confirmPurchase(Request $request): JsonResponse
    {
        $user = $request->user();
        $server = $this->resolveServer($request, 6);

        if (! $server) {
            return response()->json(['error' => 'marisa stole your access'], 403);
        }

        if (! $this->canViewLogs($request, $server)) {
            return response()->json(['error' => 'cirno tried but 6+ needed'], 403);
        }

        $request->validate(['admin_name' => 'required|string']);

        $myAdmin = $this->gameService->getAdminByName($server, $user->game_account_name);

        $this->logService->confirmPurchase(
            $server,
            $request->admin_name,
            $myAdmin->ID,
            $user->game_account_name
        );

        $this->actionLog->logFromRequest(
            ActionLogService::ADMIN_PURCHASE_CONFIRM,
            $request,
            targetName: $request->admin_name,
            targetServer: $server,
            details: ['confirmed_admin' => $request->admin_name]
        );

        return response()->json(['ok' => true]);
    }

    public function removedAdmins(Request $request): JsonResponse
    {
        $server = $this->resolveServer($request, 7);
        if (! $server) {
            return response()->json(['error' => 'need 7lvl on this server blud'], 403);
        }

        return response()->json([
            'data' => $this->logService->getRemovedAdmins(
                $server,
                $request->query('removed'),
                $request->query('removed_by'),
                $request->query('level') ? (int) $request->query('level') : null,
                $request->query('reason')
            ),
        ]);
    }

    public function gaActions(Request $request): JsonResponse
    {
        $server = $this->resolveServer($request, 8);
        if (! $server) {
            return response()->json(['error' => 'eirin says 8lvl only'], 403);
        }

        return response()->json(
            $this->logService->getGAActions(
                $server,
                (int) $request->query('page', 0),
                $request->query('ga'),
                $request->query('target'),
                $request->query('type') ? (int) $request->query('type') : null,
                $request->query('reason')
            )
        );
    }

    // 8+ only
    public function serverSettings(Request $request): JsonResponse
    {
        if (! $this->canManageServers($request)) {
            return response()->json(['error' => 'you wish'], 403);
        }

        return response()->json([
            'servers' => $this->logService->getAllServersSettings(),
        ]);
    }

    // 8+ only
    public function updateServerSettings(Request $request): JsonResponse
    {
        if (! $this->canManageServers($request)) {
            return response()->json(['error' => 'you wish'], 403);
        }

        $request->validate([
            'server' => 'required|string|in:one,two,three',
            'donate_multiplier' => 'required|integer|min:0|max:2',
            'discounts_enabled' => 'required|boolean',
            'ads_enabled' => 'required|boolean',
            'ads_link' => 'nullable|string',
            'ads_description' => 'nullable|string',
        ]);

        $this->logService->updateServerSettings(
            $request->server,
            $request->donate_multiplier,
            $request->discounts_enabled,
            $request->ads_enabled,
            $request->ads_link,
            $request->ads_description
        );

        $this->actionLog->logFromRequest(
            ActionLogService::SERVER_SETTINGS_UPDATE,
            $request,
            details: [
                'server' => $request->server,
                'donate_multiplier' => $request->donate_multiplier,
                'discounts_enabled' => $request->discounts_enabled,
                'ads_enabled' => $request->ads_enabled,
            ]
        );

        return response()->json(['ok' => true]);
    }

    private function canViewLogs(Request $request, string $server): bool
    {
        $user = $request->user();
        $myAdmin = $this->gameService->getAdminByName($server, $user->game_account_name);

        if (! $myAdmin) {
            return false;
        }

        $myLevel = $myAdmin->Adm ?? 0;
        $isGA = ($myAdmin->GA ?? 0) == 1;

        return $myLevel > 6 || ($myLevel === 6 && $isGA);
    }

    private function canManageServers(Request $request): bool
    {
        $user = $request->user();
        $myAdmin = $this->gameService->getAdminByName($user->server, $user->game_account_name);

        if (! $myAdmin) {
            return false;
        }

        $myLevel = $myAdmin->Adm ?? 0;
        $isGA = ($myAdmin->GA ?? 0) == 1;

        return $myLevel === 8 && $isGA;
    }
}
