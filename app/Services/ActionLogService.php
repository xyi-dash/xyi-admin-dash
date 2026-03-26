<?php

namespace App\Services;

use App\Models\ActionLog;
use Illuminate\Http\Request;

class ActionLogService
{
    public const LOGIN = 'login';

    public const LOGOUT = 'logout';

    public const ADMIN_AUTH = 'admin_auth';

    public const ADMIN_PANEL_ACCESS = 'admin_panel_access';

    public const CP_LOGIN = 'cp_login';

    public const ADMIN_PROMOTE = 'admin_promote';

    public const ADMIN_DEMOTE = 'admin_demote';

    public const ADMIN_WARN = 'admin_warn';

    public const ADMIN_UNWARN = 'admin_unwarn';

    public const ADMIN_REMOVE = 'admin_remove';

    public const ADMIN_GIVE_GA = 'admin_give_ga';

    public const ADMIN_REMOVE_GA = 'admin_remove_ga';

    public const ADMIN_APPOINT = 'admin_appoint';

    public const ADMIN_CONFIRM = 'admin_confirm';

    public const ADMIN_RESET_PASSWORD = 'admin_reset_password';

    public const ADMIN_PURCHASE_CONFIRM = 'admin_purchase_confirm';

    public const ADMIN_MARK_SUPPORT = 'admin_mark_support';

    public const ADMIN_REMOVE_SUPPORT = 'admin_remove_support';

    public const ADMIN_MARK_YOUTUBER = 'admin_mark_youtuber';

    public const ADMIN_REMOVE_YOUTUBER = 'admin_remove_youtuber';

    public const NEWS_CREATE = 'news_create';

    public const NEWS_UPDATE = 'news_update';

    public const NEWS_DELETE = 'news_delete';

    public const SERVER_SETTINGS_UPDATE = 'server_settings_update';

    public const CP_USER_ADD = 'cp_user_add';

    public const CP_USER_REMOVE = 'cp_user_remove';

    public const CP_USER_UPDATE = 'cp_user_update';

    public const ADMIN_CARD_CREATED = 'admin_card_created';

    public const ADMIN_CARD_APPROVED = 'admin_card_approved';

    public const ADMIN_CARD_REJECTED = 'admin_card_rejected';

    public const ADMIN_CARD_REQUIRES_CONFIRMATION = 'admin_card_requires_confirmation';

    public const ADMIN_PERMANENT_BAN_CONFIRMED = 'admin_permanent_ban_confirmed';

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

    public function logAdminAuth(int $accountId, string $accountName, string $server, string $ip): ActionLog
    {
        return $this->log(self::ADMIN_AUTH, $accountId, $accountName, $server, ip: $ip);
    }

    public function logCPLogin(int $accountId, string $accountName, string $server, string $ip): ActionLog
    {
        return $this->log(self::CP_LOGIN, $accountId, $accountName, $server, ip: $ip);
    }

    public function logAdminPanelAccess(int $accountId, string $accountName, string $server, string $ip): ActionLog
    {
        return $this->log(self::ADMIN_PANEL_ACCESS, $accountId, $accountName, $server, ip: $ip);
    }

    public static function getActionTypes(): array
    {
        return [
            self::LOGIN => 'login',
            self::LOGOUT => 'logout',
            self::ADMIN_AUTH => 'admin auth',
            self::ADMIN_PANEL_ACCESS => 'admin panel access',
            self::CP_LOGIN => 'cp login',
            self::ADMIN_PROMOTE => 'promote',
            self::ADMIN_DEMOTE => 'demote',
            self::ADMIN_WARN => 'warn',
            self::ADMIN_UNWARN => 'unwarn',
            self::ADMIN_REMOVE => 'remove',
            self::ADMIN_GIVE_GA => 'give GA',
            self::ADMIN_REMOVE_GA => 'remove GA',
            self::ADMIN_APPOINT => 'appoint',
            self::ADMIN_CONFIRM => 'confirm',
            self::ADMIN_RESET_PASSWORD => 'reset password',
            self::ADMIN_PURCHASE_CONFIRM => 'purchase confirm',
            self::ADMIN_MARK_SUPPORT => 'mark support',
            self::ADMIN_REMOVE_SUPPORT => 'remove support',
            self::ADMIN_MARK_YOUTUBER => 'mark youtuber',
            self::ADMIN_REMOVE_YOUTUBER => 'remove youtuber',
            self::NEWS_CREATE => 'news create',
            self::NEWS_UPDATE => 'news update',
            self::NEWS_DELETE => 'news delete',
            self::SERVER_SETTINGS_UPDATE => 'server settings',
            self::CP_USER_ADD => 'cp user add',
            self::CP_USER_REMOVE => 'cp user remove',
            self::CP_USER_UPDATE => 'cp user update',
            self::ADMIN_CARD_CREATED => 'admin card created',
            self::ADMIN_CARD_APPROVED => 'admin card approved',
            self::ADMIN_CARD_REJECTED => 'admin card rejected',
            self::ADMIN_CARD_REQUIRES_CONFIRMATION => 'admin card requires confirmation',
            self::ADMIN_PERMANENT_BAN_CONFIRMED => 'admin permanent ban confirmed',
        ];
    }
}
