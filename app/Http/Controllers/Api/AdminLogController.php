<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminLogService;
use App\Services\GameAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    public function __construct(
        private AdminLogService $logService,
        private GameAccountService $gameService
    ) {}

    // 6+ can view this
    public function adminActions(Request $request): JsonResponse
    {
        if (!$this->canViewLogs($request)) {
            return response()->json(['error' => 'nope'], 403);
        }

        $user = $request->user();
        $myLevel = $request->attributes->get('admin_level');

        return response()->json(
            $this->logService->getAdminActions(
                $user->server,
                $myLevel,
                (int) $request->query('page', 0),
                $request->query('admin'),
                $request->query('player'),
                $request->query('cmd'),
                $request->query('reason')
            )
        );
    }

    // 6+ can view
    public function warnings(Request $request): JsonResponse
    {
        if (!$this->canViewLogs($request)) {
            return response()->json(['error' => 'nope'], 403);
        }

        $user = $request->user();

        return response()->json([
            'data' => $this->logService->getIssuedWarnings(
                $user->server,
                $request->query('issued_by'),
                $request->query('issued_to'),
                $request->query('reason')
            )
        ]);
    }

    // 6+ can view
    public function purchases(Request $request): JsonResponse
    {
        if (!$this->canViewLogs($request)) {
            return response()->json(['error' => 'nope'], 403);
        }

        $user = $request->user();

        return response()->json(
            $this->logService->getPurchases(
                $user->server,
                (int) $request->query('page', 0),
                $request->query('admin'),
                $request->query('vk'),
                $request->query('type') ? (int) $request->query('type') : null,
                $request->query('level') ? (int) $request->query('level') : null
            )
        );
    }

    // 6+ can confirm purchases pechenki
    public function confirmPurchase(Request $request): JsonResponse
    {
        if (!$this->canViewLogs($request)) {
            return response()->json(['error' => 'nope'], 403);
        }

        $request->validate(['admin_name' => 'required|string']);
        
        $user = $request->user();
        $myAdmin = $this->gameService->getAdminByName($user->server, $user->game_account_name);

        $this->logService->confirmPurchase(
            $user->server,
            $request->admin_name,
            $myAdmin->ID,
            $user->game_account_name
        );

        return response()->json(['ok' => true]);
    }

    // 7lvls can view
    public function removedAdmins(Request $request): JsonResponse
    {
        $myLevel = $request->attributes->get('admin_level');
        
        if ($myLevel < 7) {
            return response()->json(['error' => 'need 7lvl blud'], 403);
        }

        $user = $request->user();

        return response()->json([
            'data' => $this->logService->getRemovedAdmins(
                $user->server,
                $request->query('removed'),
                $request->query('removed_by'),
                $request->query('level') ? (int) $request->query('level') : null,
                $request->query('reason')
            )
        ]);
    }

    // 8lvl only
    public function gaActions(Request $request): JsonResponse
    {
        $myLevel = $request->attributes->get('admin_level');
        
        if ($myLevel < 8) {
            return response()->json(['error' => '8lvl only sorry'], 403);
        }

        $user = $request->user();

        return response()->json(
            $this->logService->getGAActions(
                $user->server,
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
        if (!$this->canManageServers($request)) {
            return response()->json(['error' => 'you wish'], 403);
        }

        return response()->json([
            'servers' => $this->logService->getAllServersSettings()
        ]);
    }

    // 8+ only
    public function updateServerSettings(Request $request): JsonResponse
    {
        if (!$this->canManageServers($request)) {
            return response()->json(['error' => 'you wish'], 403);
        }

        $request->validate([
            'server' => 'required|string|in:one,two,three,four',
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

        return response()->json(['ok' => true]);
    }

    // 6+
    private function canViewLogs(Request $request): bool
    {
        $myLevel = $request->attributes->get('admin_level');
        $user = $request->user();
        $myAdmin = $this->gameService->getAdminByName($user->server, $user->game_account_name);
        $isGA = $myAdmin && ($myAdmin->GA ?? 0) == 1;

        return $myLevel > 6 || ($myLevel === 6 && $isGA);
    }

    // 8+
    private function canManageServers(Request $request): bool
    {
        $myLevel = $request->attributes->get('admin_level');
        $user = $request->user();
        $myAdmin = $this->gameService->getAdminByName($user->server, $user->game_account_name);
        $isGA = $myAdmin && ($myAdmin->GA ?? 0) == 1;

        return $myLevel === 8 && $isGA;
    }
}

