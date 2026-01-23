<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AdminLogService
{
    private const SERVER_CONNECTIONS = [
        'one' => 'gangwar',
        'two' => 'gangwar2',
        'three' => 'gangwar3',
    ];

    public const TYPE_WARN = 1;

    public const TYPE_UNWARN = 2;

    public const TYPE_PROMOTE = 3;

    public const TYPE_DEMOTE = 4;

    public const TYPE_REMOVE = 5;

    public const TYPE_APPOINT = 6;

    public const TYPE_BUY_CONFIRM = 7;

    public const TYPE_CHANGE_PIP = 8;

    public const TYPE_RESET_PASSWORD = 9;

    public const TYPE_CONFIRM = 10;

    // people actually pay money for these this is wild
    public const BUY_ADMIN = 1;

    public const BUY_PROMOTE = 2;

    public const BUY_UNWARN = 3;

    private function conn(string $server): ?string
    {
        return self::SERVER_CONNECTIONS[$server] ?? null;
    }

    private function escapeLike(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }

    public function getAdminActions(
        string $server,
        int $viewerLevel,
        int $page = 0,
        ?string $admin = null,
        ?string $player = null,
        ?string $cmd = null,
        ?string $reason = null,
        bool $withKills = false
    ): array {
        $connection = $this->conn($server);
        if (! $connection) {
            return ['data' => [], 'total' => 0];
        }

        $query = DB::connection($connection)->table('logsadmin');

        $startDate = now()->subDays($page)->startOfDay();
        $endDate = now()->subDays($page - 1)->startOfDay();

        $query->where('Date', '>', $startDate)
            ->where('Date', '<=', $endDate)
            ->where('ALevel', '<=', $viewerLevel);

        if ($admin) {
            $query->where('Admin', $admin);
        }
        if ($player) {
            $query->where('Player', $player);
        }
        if ($cmd) {
            $query->where('CMD', $cmd);
        }
        if ($reason) {
            $query->where('Reason', 'like', '%'.$this->escapeLike($reason).'%');
        }

        $total = $query->count();

        $rows = $query->orderByDesc('Date')->limit(50)->get();

        $killsMap = [];
        if ($withKills && $rows->isNotEmpty()) {
            $playerNames = $rows->pluck('Player')->unique()->filter()->values()->toArray();
            if (! empty($playerNames)) {
                $killsMap = DB::connection($connection)
                    ->table('a27ccount')
                    ->whereIn('Name', $playerNames)
                    ->pluck('Kills', 'Name')
                    ->toArray();
            }
        }

        $data = $rows->map(fn ($row) => [
            'id' => $row->ID ?? null,
            'admin_id' => $row->idadm ?? null,
            'admin' => $row->Admin,
            'player' => $row->Player,
            'cmd' => $row->CMD,
            'amount' => $row->Amount,
            'reason' => $row->Reason,
            'date' => $row->Date,
            'admin_level' => $row->ALevel ?? null,
            'player_kills' => $withKills ? ($killsMap[$row->Player] ?? null) : null,
        ])->toArray();

        return ['data' => $data, 'total' => $total, 'page' => $page];
    }

    public function getIssuedWarnings(
        string $server,
        ?string $issuedBy = null,
        ?string $issuedTo = null,
        ?string $reason = null
    ): array {
        $connection = $this->conn($server);
        if (! $connection) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('logsadmin2')
            ->where('type', self::TYPE_WARN);

        if ($issuedBy) {
            $query->where('admin', $issuedBy);
        }
        if ($issuedTo) {
            $query->where('name', $issuedTo);
        }
        if ($reason) {
            $query->where('reason', 'like', '%'.$this->escapeLike($reason).'%');
        }

        return $query->orderByDesc('date')
            ->limit(30)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'admin_id' => $row->idadm ?? null,
                'admin' => $row->admin,
                'target' => $row->name,
                'reason' => $row->reason,
                'date' => $row->date,
            ])
            ->toArray();
    }

    public function getRemovedAdmins(
        string $server,
        ?string $removedAdmin = null,
        ?string $removedBy = null,
        ?int $level = null,
        ?string $reason = null
    ): array {
        $connection = $this->conn($server);
        if (! $connection) {
            return [];
        }

        $query = DB::connection($connection)
            ->table('logsadmin2')
            ->where('type', self::TYPE_REMOVE);

        if ($removedAdmin) {
            $query->where('name', $removedAdmin);
        }
        if ($removedBy) {
            $query->where('admin', $removedBy);
        }
        if ($level) {
            $query->where('kolvo', $level);
        }
        if ($reason) {
            $query->where('reason', 'like', '%'.$this->escapeLike($reason).'%');
        }

        return $query->orderByDesc('date')
            ->limit(30)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'admin_id' => $row->idadm ?? null,
                'admin' => $row->admin,
                'target' => $row->name,
                'level' => $row->kolvo ?? null,
                'reason' => $row->reason,
                'date' => $row->date,
            ])
            ->toArray();
    }

    public function getPurchases(
        string $server,
        int $page = 0,
        ?string $admin = null,
        ?string $vkPage = null,
        ?int $type = null,
        ?int $level = null
    ): array {
        $connection = $this->conn($server);
        if (! $connection) {
            return ['data' => [], 'total' => 0];
        }

        $query = DB::connection($connection)
            ->table('b27uy')
            ->where('Status', 0)
            ->where('Status2', 1);

        $startDate = now()->subDays($page)->startOfDay();
        $endDate = now()->subDays($page - 1)->startOfDay();

        $query->where('Date', '>', $startDate)->where('Date', '<=', $endDate);

        if ($admin) {
            $query->where('NameGame', $admin);
        }
        if ($vkPage) {
            $query->where('NameSkype', $vkPage);
        }
        if ($type) {
            $query->where('Type', $type);
        }
        if ($level) {
            $query->where('Level', $level);
        }

        $total = $query->count();

        $data = $query->orderByDesc('Date')
            ->limit(50)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->ID ?? null,
                'admin_id' => $row->admacc ?? null,
                'name' => $row->NameGame,
                'vk_page' => $row->NameSkype,
                'type' => $row->Type,
                'type_name' => $this->buyTypeName($row->Type),
                'level' => $row->Level,
                'date' => $row->Date,
                'needs_confirm' => ($row->admgive ?? 0) == 1,
            ])
            ->toArray();

        return ['data' => $data, 'total' => $total, 'page' => $page];
    }

    public function getGAActions(
        string $server,
        int $page = 0,
        ?string $gaAdmin = null,
        ?string $target = null,
        ?int $actionType = null,
        ?string $reason = null
    ): array {
        $connection = $this->conn($server);
        if (! $connection) {
            return ['data' => [], 'total' => 0];
        }

        $query = DB::connection($connection)->table('logsadmin2');

        $query->whereNotIn('type', [7, 11, 12]);

        $startDate = now()->subDays($page)->startOfDay();
        $endDate = now()->subDays($page - 1)->startOfDay();

        $query->where('date', '>', $startDate)->where('date', '<=', $endDate);

        if ($gaAdmin) {
            $query->where('admin', $gaAdmin);
        }
        if ($target) {
            $query->where('name', $target);
        }
        if ($actionType) {
            $query->where('type', $actionType);
        }
        if ($reason) {
            $query->where('reason', 'like', '%'.$this->escapeLike($reason).'%');
        }

        $total = $query->count();

        $data = $query->orderByDesc('date')
            ->limit(50)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'admin_id' => $row->idadm ?? null,
                'admin' => $row->admin,
                'target' => $row->name,
                'type' => $row->type,
                'type_name' => $this->actionTypeName($row->type),
                'amount' => $row->kolvo ?? null,
                'reason' => $row->reason,
                'date' => $row->date,
                'can_cancel' => ($row->status ?? 0) == 1,
            ])
            ->toArray();

        return ['data' => $data, 'total' => $total, 'page' => $page];
    }

    public function getServerSettings(string $server): ?array
    {
        $connection = $this->conn($server);
        if (! $connection) {
            return null;
        }

        $s = DB::connection($connection)->table('s27erver')->first();
        if (! $s) {
            return null;
        }

        return [
            'donate_multiplier' => $s->donat ?? 0,
            'discounts_enabled' => ($s->skidki ?? 0) == 1,
            'ads_enabled' => ($s->rekl ?? 0) == 1,
            'ads_link' => ($s->rekl1 ?? '-') === '-' ? null : $s->rekl1,
            'ads_description' => ($s->rekl2 ?? '-') === '-' ? null : $s->rekl2,
        ];
    }

    public function updateServerSettings(
        string $server,
        int $donateMultiplier,
        bool $discountsEnabled,
        bool $adsEnabled,
        ?string $adsLink = null,
        ?string $adsDescription = null
    ): bool {
        $connection = $this->conn($server);
        if (! $connection) {
            return false;
        }

        return DB::connection($connection)
            ->table('s27erver')
            ->limit(1)
            ->update([
                'donat' => $donateMultiplier,
                'skidki' => $discountsEnabled ? 1 : 0,
                'rekl' => $adsEnabled ? 1 : 0,
                'rekl1' => $adsLink ?? '-',
                'rekl2' => $adsDescription ?? '-',
            ]) !== false;
    }

    public function getAllServersSettings(): array
    {
        $result = [];
        foreach (array_keys(self::SERVER_CONNECTIONS) as $server) {
            $result[$server] = $this->getServerSettings($server);
        }

        return $result;
    }

    public function confirmPurchase(string $server, string $adminName, int $confirmerId, string $confirmerName): bool
    {
        $connection = $this->conn($server);
        if (! $connection) {
            return false;
        }

        DB::connection($connection)
            ->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$adminName])
            ->update([
                'Kem' => "SYSTEM ({$confirmerName})",
                'admgive' => 0,
            ]);

        DB::connection($connection)
            ->table('b27uy')
            ->where('NameGame', $adminName)
            ->orderByDesc('ID')
            ->limit(1)
            ->update(['admgive' => 0]);

        DB::connection($connection)
            ->table('logsadmin2')
            ->insert([
                'idadm' => $confirmerId,
                'name' => $adminName,
                'ip' => ' ',
                'admin' => $confirmerName,
                'ipp' => request()->ip(),
                'type' => self::TYPE_BUY_CONFIRM,
                'kolvo' => 0,
                'reason' => ' ',
                'date' => now('Europe/Moscow'),
            ]);

        return true;
    }

    private function buyTypeName(int $type): string
    {
        return match ($type) {
            self::BUY_ADMIN => 'buy_admin',
            self::BUY_PROMOTE => 'promotion',
            self::BUY_UNWARN => 'remove_warning',
            default => 'unknown_transaction_type_what_the_wang',
        };
    }

    private function actionTypeName(int $type): string
    {
        return match ($type) {
            self::TYPE_WARN => 'warn',
            self::TYPE_UNWARN => 'unwarn',
            self::TYPE_PROMOTE => 'promote',
            self::TYPE_DEMOTE => 'demote',
            self::TYPE_REMOVE => 'remove',
            self::TYPE_APPOINT => 'appoint',
            self::TYPE_BUY_CONFIRM => 'buy_confirm',
            self::TYPE_CHANGE_PIP => 'change_pip',
            self::TYPE_RESET_PASSWORD => 'reset_password',
            self::TYPE_CONFIRM => 'confirm',
            default => 'reimu_broke_something',
        };
    }
}
