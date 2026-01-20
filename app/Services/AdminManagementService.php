<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

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
            return ['success' => false, 'error' => 'max_warnings'];
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
            return ['success' => false, 'error' => 'already_lowest'];
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

    public function getAvailableActions(int $actorLevel, bool $actorIsGA, int $targetLevel): array
    {
        $actions = [];

        // hakurei barrier
        $canManage = $actorLevel > $targetLevel || ($actorLevel === 6 && $actorIsGA && $targetLevel < 6);

        if (!$canManage) {
            return $actions;
        }

        if ($actorLevel >= 7 || ($actorLevel === 6 && $actorIsGA)) {
            $actions[] = 'warn';
            $actions[] = 'unwarn';
            $actions[] = 'reset_password';
            $actions[] = 'confirm';
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
            'date' => now(),
        ]);
    }

    private function conn(string $server): ?string
    {
        return self::SERVER_CONNECTIONS[$server] ?? null;
    }
}
