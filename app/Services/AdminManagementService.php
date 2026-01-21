<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * where admin dreams come to die and get resurrected as database rows
 * every method here is a small funeral for someones career or ego
 * 
 * if you're reading this and thinking "why is this so long" - because
 * managing humans is complicated and i hate it. go ask yukari.
 */
class AdminManagementService
{
    private const SERVER_CONNECTIONS = [
        'one' => 'gangwar',
        'two' => 'gangwar2',
        'three' => 'gangwar3',
    ];

    public function __construct(
        private ActionLogService $actionLog,
        private GameAccountService $gameService
    ) {}

    public function warn(
        string $server,
        string $targetName,
        string $reason,
        int $actorId,
        string $actorName,
        string $actorIp
    ): array {
        $conn = $this->conn($server);
        $target = $this->getAdmin($conn, $targetName);

        if (!$target) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        if ($target->Preds >= 3) {
            return ['success' => false, 'error' => 'three_strikes_youre_already_out'];
        }

        $newWarns = $target->Preds + 1;

        DB::connection($conn)->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$targetName])
            ->update(['Preds' => $newWarns]);

        $this->logToGameDb($conn, $actorId, $targetName, $target->IP ?? '', $actorName, $actorIp, 1, $newWarns, $reason);

        $this->actionLog->log(
            ActionLogService::ADMIN_WARN,
            $actorId, $actorName, $server,
            $target->idAccount ?? $target->ID, $targetName, $server,
            ['old_warns' => $target->Preds, 'new_warns' => $newWarns, 'reason' => $reason],
            $actorIp
        );

        return ['success' => true, 'new_warns' => $newWarns];
    }

    public function unwarn(
        string $server,
        string $targetName,
        string $reason,
        int $actorId,
        string $actorName,
        string $actorIp
    ): array {
        $conn = $this->conn($server);
        $target = $this->getAdmin($conn, $targetName);

        if (!$target) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        if ($target->Preds < 1) {
            return ['success' => false, 'error' => 'no_warnings'];
        }

        $newWarns = $target->Preds - 1;

        DB::connection($conn)->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$targetName])
            ->update(['Preds' => $newWarns]);

        $this->logToGameDb($conn, $actorId, $targetName, $target->IP ?? '', $actorName, $actorIp, 2, $newWarns, $reason);

        $this->actionLog->log(
            ActionLogService::ADMIN_UNWARN,
            $actorId, $actorName, $server,
            $target->idAccount ?? $target->ID, $targetName, $server,
            ['old_warns' => $target->Preds, 'new_warns' => $newWarns, 'reason' => $reason],
            $actorIp
        );

        return ['success' => true, 'new_warns' => $newWarns];
    }

    public function promote(
        string $server,
        string $targetName,
        string $reason,
        int $actorId,
        string $actorName,
        int $actorLevel,
        string $actorIp
    ): array {
        $conn = $this->conn($server);
        $target = $this->getAdmin($conn, $targetName);

        if (!$target) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        $maxPromoteTo = $actorLevel === 8 ? 7 : ($actorLevel === 7 ? 6 : 0);

        if ($target->Adm >= $maxPromoteTo) {
            return ['success' => false, 'error' => 'cannot_promote_higher'];
        }

        $newLevel = $target->Adm + 1;

        DB::connection($conn)->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$targetName])
            ->update(['Adm' => $newLevel]);

        $this->logToGameDb($conn, $actorId, $targetName, $target->IP ?? '', $actorName, $actorIp, 3, $newLevel, $reason);

        $this->actionLog->log(
            ActionLogService::ADMIN_PROMOTE,
            $actorId, $actorName, $server,
            $target->idAccount ?? $target->ID, $targetName, $server,
            ['old_level' => $target->Adm, 'new_level' => $newLevel, 'reason' => $reason],
            $actorIp
        );

        return ['success' => true, 'new_level' => $newLevel];
    }

    public function demote(
        string $server,
        string $targetName,
        string $reason,
        int $actorId,
        string $actorName,
        int $actorLevel,
        string $actorIp
    ): array {
        $conn = $this->conn($server);
        $target = $this->getAdmin($conn, $targetName);

        if (!$target) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        if ($target->Adm >= $actorLevel) {
            return ['success' => false, 'error' => 'cannot_demote_same_or_higher'];
        }

        if ($target->Adm < 2) {
            return ['success' => false, 'error' => 'cant_go_lower_than_rock_bottom'];
        }

        $newLevel = $target->Adm - 1;

        DB::connection($conn)->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$targetName])
            ->update(['Adm' => $newLevel]);

        $this->logToGameDb($conn, $actorId, $targetName, $target->IP ?? '', $actorName, $actorIp, 4, $newLevel, $reason);

        $this->actionLog->log(
            ActionLogService::ADMIN_DEMOTE,
            $actorId, $actorName, $server,
            $target->idAccount ?? $target->ID, $targetName, $server,
            ['old_level' => $target->Adm, 'new_level' => $newLevel, 'reason' => $reason],
            $actorIp
        );

        return ['success' => true, 'new_level' => $newLevel];
    }

    public function remove(
        string $server,
        string $targetName,
        string $reason,
        int $actorId,
        string $actorName,
        int $actorLevel,
        string $actorIp
    ): array {
        $conn = $this->conn($server);
        $target = $this->getAdmin($conn, $targetName);

        if (!$target) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        if ($target->Adm >= $actorLevel) {
            return ['success' => false, 'error' => 'cannot_remove_same_or_higher'];
        }

        $oldLevel = $target->Adm;
        $targetAccId = $target->idAccount ?? $target->ID;

        DB::connection($conn)->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$targetName])
            ->delete();

        $this->logToGameDb($conn, $actorId, $targetName, $target->IP ?? '', $actorName, $actorIp, 5, $oldLevel, $reason);

        $this->actionLog->log(
            ActionLogService::ADMIN_REMOVE,
            $actorId, $actorName, $server,
            $targetAccId, $targetName, $server,
            ['level' => $oldLevel, 'reason' => $reason],
            $actorIp
        );

        return ['success' => true];
    }

    public function resetPassword(
        string $server,
        string $targetName,
        int $actorId,
        string $actorName,
        int $actorLevel,
        string $actorIp
    ): array {
        $conn = $this->conn($server);
        $target = $this->getAdmin($conn, $targetName);

        if (!$target) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        if ($target->Adm >= $actorLevel) {
            return ['success' => false, 'error' => 'cannot_reset_same_or_higher'];
        }

        DB::connection($conn)->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$targetName])
            ->update(['Password' => '', 'Salt' => '']);

        $this->logToGameDb($conn, $actorId, $targetName, $target->IP ?? '', $actorName, $actorIp, 9, 0, ' ');

        $this->actionLog->log(
            ActionLogService::ADMIN_RESET_PASSWORD,
            $actorId, $actorName, $server,
            $target->idAccount ?? $target->ID, $targetName, $server,
            null,
            $actorIp
        );

        return ['success' => true];
    }

    public function confirm(
        string $server,
        string $targetName,
        int $actorId,
        string $actorName,
        string $actorIp
    ): array {
        $conn = $this->conn($server);
        $target = $this->getAdmin($conn, $targetName);

        if (!$target) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        if (($target->admgive ?? 0) == 0) {
            return ['success' => false, 'error' => 'already_confirmed'];
        }

        $datep = date('d.m.Y');

        DB::connection($conn)->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$targetName])
            ->update([
                'Kem' => "SYSTEM ({$actorName})",
                'Date' => $datep,
                'admgive' => 0,
            ]);

        DB::connection($conn)->table('b27uy')
            ->where('NameGame', $targetName)
            ->orderByDesc('ID')
            ->limit(1)
            ->update(['admgive' => 0]);

        $this->logToGameDb($conn, $actorId, $targetName, $target->IP ?? '', $actorName, $actorIp, 10, 0, ' ');

        $this->actionLog->log(
            ActionLogService::ADMIN_CONFIRM,
            $actorId, $actorName, $server,
            $target->idAccount ?? $target->ID, $targetName, $server,
            null,
            $actorIp
        );

        return ['success' => true];
    }

    public function giveGA(
        string $server,
        string $targetName,
        string $reason,
        int $actorId,
        string $actorName,
        string $actorIp
    ): array {
        $conn = $this->conn($server);
        $target = $this->getAdmin($conn, $targetName);

        if (!$target) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        if (($target->GA ?? 0) == 1) {
            return ['success' => false, 'error' => 'already_ga'];
        }

        DB::connection($conn)->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$targetName])
            ->update(['GA' => 1]);

        $this->logToGameDb($conn, $actorId, $targetName, $target->IP ?? '', $actorName, $actorIp, 11, 0, $reason);

        $this->actionLog->log(
            ActionLogService::ADMIN_GIVE_GA,
            $actorId, $actorName, $server,
            $target->idAccount ?? $target->ID, $targetName, $server,
            ['reason' => $reason],
            $actorIp
        );

        return ['success' => true];
    }

    public function removeGA(
        string $server,
        string $targetName,
        string $reason,
        int $actorId,
        string $actorName,
        string $actorIp
    ): array {
        $conn = $this->conn($server);
        $target = $this->getAdmin($conn, $targetName);

        if (!$target) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        if (($target->GA ?? 0) == 0) {
            return ['success' => false, 'error' => 'not_ga'];
        }

        DB::connection($conn)->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$targetName])
            ->update(['GA' => 0]);

        $this->logToGameDb($conn, $actorId, $targetName, $target->IP ?? '', $actorName, $actorIp, 12, 0, $reason);

        $this->actionLog->log(
            ActionLogService::ADMIN_REMOVE_GA,
            $actorId, $actorName, $server,
            $target->idAccount ?? $target->ID, $targetName, $server,
            ['reason' => $reason],
            $actorIp
        );

        return ['success' => true];
    }

    public function markAsSupport(
        string $server,
        string $targetName,
        string $reason,
        int $actorId,
        string $actorName,
        string $actorIp
    ): array {
        $conn = $this->conn($server);
        $target = $this->getAdmin($conn, $targetName);

        if (!$target) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        if (($target->is_support ?? 0) == 1) {
            return ['success' => false, 'error' => 'already_support'];
        }

        DB::connection($conn)->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$targetName])
            ->update(['is_support' => 1]);

        $this->logToGameDb($conn, $actorId, $targetName, $target->IP ?? '', $actorName, $actorIp, 13, 0, $reason);

        $this->actionLog->log(
            ActionLogService::ADMIN_MARK_SUPPORT,
            $actorId, $actorName, $server,
            $target->idAccount ?? $target->ID, $targetName, $server,
            ['reason' => $reason],
            $actorIp
        );

        return ['success' => true];
    }

    public function removeSupport(
        string $server,
        string $targetName,
        string $reason,
        int $actorId,
        string $actorName,
        string $actorIp
    ): array {
        $conn = $this->conn($server);
        $target = $this->getAdmin($conn, $targetName);

        if (!$target) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        if (($target->is_support ?? 0) == 0) {
            return ['success' => false, 'error' => 'not_support'];
        }

        DB::connection($conn)->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$targetName])
            ->update(['is_support' => 0]);

        // auth.monser.ru couldn't do this. we are superior! marginally.
        $this->logToGameDb($conn, $actorId, $targetName, $target->IP ?? '', $actorName, $actorIp, 15, 0, $reason);

        $this->actionLog->log(
            ActionLogService::ADMIN_REMOVE_SUPPORT,
            $actorId, $actorName, $server,
            $target->idAccount ?? $target->ID, $targetName, $server,
            ['reason' => $reason],
            $actorIp
        );

        return ['success' => true];
    }

    public function markAsYouTuber(
        string $server,
        string $targetName,
        string $reason,
        int $actorId,
        string $actorName,
        string $actorIp
    ): array {
        $conn = $this->conn($server);
        $target = $this->getAdmin($conn, $targetName);

        if (!$target) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        if (($target->is_media ?? 0) == 1) {
            return ['success' => false, 'error' => 'already_youtuber'];
        }

        DB::connection($conn)->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$targetName])
            ->update(['is_media' => 1]);

        $this->logToGameDb($conn, $actorId, $targetName, $target->IP ?? '', $actorName, $actorIp, 14, 0, $reason);

        $this->actionLog->log(
            ActionLogService::ADMIN_MARK_YOUTUBER,
            $actorId, $actorName, $server,
            $target->idAccount ?? $target->ID, $targetName, $server,
            ['reason' => $reason],
            $actorIp
        );

        return ['success' => true];
    }

    public function removeYouTuber(
        string $server,
        string $targetName,
        string $reason,
        int $actorId,
        string $actorName,
        string $actorIp
    ): array {
        $conn = $this->conn($server);
        $target = $this->getAdmin($conn, $targetName);

        if (!$target) {
            return ['success' => false, 'error' => 'admin_not_found'];
        }

        if (($target->is_media ?? 0) == 0) {
            return ['success' => false, 'error' => 'not_youtuber'];
        }

        DB::connection($conn)->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$targetName])
            ->update(['is_media' => 0]);

        $this->logToGameDb($conn, $actorId, $targetName, $target->IP ?? '', $actorName, $actorIp, 16, 0, $reason);

        $this->actionLog->log(
            ActionLogService::ADMIN_REMOVE_YOUTUBER,
            $actorId, $actorName, $server,
            $target->idAccount ?? $target->ID, $targetName, $server,
            ['reason' => $reason],
            $actorIp
        );

        return ['success' => true];
    }

    public function addAdmin(
        string $server,
        string $targetName,
        int $level,
        string $reason,
        int $actorId,
        string $actorName,
        int $actorLevel,
        bool $actorIsGA,
        string $actorIp
    ): array {
        $conn = $this->conn($server);
        if (!$conn) {
            return ['success' => false, 'error' => 'invalid_server'];
        }

        if ($actorLevel < 7 && !($actorLevel === 6 && $actorIsGA)) {
            return ['success' => false, 'error' => 'insufficient_level'];
        }

        $maxLevel = match(true) {
            $actorLevel >= 7 => 6,
            $actorLevel === 6 || $actorIsGA => 5,
            default => 0,
        };

        if ($level < 1 || $level > $maxLevel) {
            return ['success' => false, 'error' => 'invalid_level'];
        }

        $account = DB::connection($conn)
            ->table('a27ccount')
            ->whereRaw('BINARY Name = ?', [$targetName])
            ->first(['ID', 'Name', 'IpLog']);

        if (!$account) {
            return ['success' => false, 'error' => 'player_not_found'];
        }

        $existingAdmin = $this->getAdmin($conn, $targetName);
        if ($existingAdmin) {
            return ['success' => false, 'error' => 'already_admin'];
        }

        $datep = date('d.m.Y');

        DB::connection($conn)->table('a27dmins')->insert([
            'idAccount' => $account->ID,
            'Name' => $account->Name,
            'Adm' => $level,
            'Kem' => $actorName,
            'Date' => $datep,
            'IP' => 'None',
            'admgive' => 0,
        ]);

        $this->logToGameDb(
            $conn,
            $actorId,
            $targetName,
            $account->IpLog ?? '',
            $actorName,
            $actorIp,
            6,
            $level,
            $reason ?: ' '
        );

        $this->actionLog->log(
            ActionLogService::ADMIN_APPOINT,
            $actorId, $actorName, $server,
            $account->ID, $targetName, $server,
            ['level' => $level, 'reason' => $reason],
            $actorIp
        );

        return ['success' => true, 'level' => $level];
    }

    public function getAvailableActions(int $actorLevel, bool $actorIsGA, int $targetLevel): array
    {
        $actions = [];

        $canManage = $actorLevel > $targetLevel || ($actorLevel === 6 && $actorIsGA && $targetLevel < 6);

        if (!$canManage) {
            return $actions;
        }

        if ($actorLevel >= 7 || ($actorLevel === 6 && $actorIsGA)) {
            $actions[] = 'warn';
            $actions[] = 'unwarn';
            $actions[] = 'reset_password';
            $actions[] = 'confirm';
            $actions[] = 'mark_support';
            $actions[] = 'remove_support';
            $actions[] = 'mark_youtuber';
            $actions[] = 'remove_youtuber';
        }

        if ($actorLevel >= 7) {
            $actions[] = 'promote';
            $actions[] = 'demote';
            $actions[] = 'remove';
        }

        if ($actorLevel >= 8) {
            $actions[] = 'give_ga';
            $actions[] = 'remove_ga';
        }

        return $actions;
    }

    public function getLevelRequirements(int $level, string $server): array
    {
        $reqs = match($level) {
            1 => match($server) {
                'two' => ['hours' => 14, 'punishments' => 300, 'reports' => 500],
                'three' => ['hours' => 14, 'punishments' => 250, 'reports' => 450],
                default => ['hours' => 14, 'punishments' => 200, 'reports' => 400],
            },
            2 => match($server) {
                'two' => ['hours' => 28, 'punishments' => 500, 'reports' => 700],
                'three' => ['hours' => 28, 'punishments' => 450, 'reports' => 650],
                default => ['hours' => 28, 'punishments' => 400, 'reports' => 600],
            },
            3 => match($server) {
                'two' => ['hours' => 42, 'punishments' => 800, 'reports' => 1200],
                'three' => ['hours' => 42, 'punishments' => 700, 'reports' => 1100],
                default => ['hours' => 42, 'punishments' => 600, 'reports' => 1000],
            },
            4 => match($server) {
                'two' => ['hours' => 56, 'punishments' => 1200, 'reports' => 2000],
                'three' => ['hours' => 56, 'punishments' => 1100, 'reports' => 1900],
                default => ['hours' => 56, 'punishments' => 1000, 'reports' => 1800],
            },
            default => ['hours' => 0, 'punishments' => 0, 'reports' => 0],
        };

        return $reqs;
    }

    private function getAdmin(string $conn, string $name): ?object
    {
        return DB::connection($conn)
            ->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$name])
            ->first();
    }

    /**
     * shove it into logsadmin2 so arkxa bot can pick it up
     * and spam someones dms about it. truly, peak automation.
     */
    private function logToGameDb(
        string $conn,
        int $actorId,
        string $targetName,
        string $targetIp,
        string $actorName,
        string $actorIp,
        int $type,
        int $amount,
        string $reason
    ): void {
        DB::connection($conn)->table('logsadmin2')->insert([
            'idadm' => $actorId,
            'name' => $targetName,
            'ip' => $targetIp,
            'admin' => $actorName,
            'ipp' => $actorIp,
            'type' => $type,
            'kolvo' => $amount,
            'reason' => $reason,
            'date' => now('Europe/Moscow'),
        ]);
    }

    private function conn(string $server): ?string
    {
        return self::SERVER_CONNECTIONS[$server] ?? null;
    }
}
