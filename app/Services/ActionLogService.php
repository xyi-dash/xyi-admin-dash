<?php

namespace App\Services;

use App\Models\ActionLog;
use Illuminate\Http\Request;

class ActionLogService
{
    public const LOGIN = 'login';
    public const LOGOUT = 'logout';
    public const ADMIN_PROMOTE = 'admin_promote';
    public const ADMIN_DEMOTE = 'admin_demote';
    public const ADMIN_WARN = 'admin_warn';
    public const ADMIN_UNWARN = 'admin_unwarn';
    public const ADMIN_REMOVE = 'admin_remove';
    public const ADMIN_GIVE_GA = 'admin_give_ga';
    public const ADMIN_REMOVE_GA = 'admin_remove_ga';

    public function log(
        string $actionType,
        int $actorId,
        string $actorName,
        string $actorServer,
        ?int $targetId = null,
        ?string $targetName = null,
        ?string $targetServer = null,
        ?array $details = null,
        ?string $ip = null
    ): ActionLog {
        return ActionLog::create([
            'action_type' => $actionType,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'actor_server' => $actorServer,
            'target_id' => $targetId,
            'target_name' => $targetName,
            'target_server' => $targetServer,
            'details' => $details,
            'ip_address' => $ip,
        ]);
    }

    public function logFromRequest(
        string $actionType,
        Request $request,
        ?int $targetId = null,
        ?string $targetName = null,
        ?string $targetServer = null,
        ?array $details = null
    ): ActionLog {
        $user = $request->user();
        
        return $this->log(
            $actionType,
            $user->game_account_id,
            $user->game_account_name,
            $user->server,
            $targetId,
            $targetName,
            $targetServer,
            $details,
            $request->ip()
        );
    }

    public function logLogin(int $accountId, string $accountName, string $server, string $ip): ActionLog
    {
        return $this->log(self::LOGIN, $accountId, $accountName, $server, ip: $ip);
    }

    public function logLogout(int $accountId, string $accountName, string $server, string $ip): ActionLog
    {
        return $this->log(self::LOGOUT, $accountId, $accountName, $server, ip: $ip);
    }
}

