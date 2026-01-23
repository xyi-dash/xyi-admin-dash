<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PlayerLogService
{
    private const SERVER_CONNECTIONS = [
        'one' => 'gangwar',
        'two' => 'gangwar2',
        'three' => 'gangwar3',
    ];

    private const SERVER_NAMES = [
        'one' => 'Server 01',
        'two' => 'Server 02',
        'three' => 'Server 03',
    ];

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

    public function getAvailableServers(string $nickname): array
    {
        $available = [];

        foreach (self::SERVER_CONNECTIONS as $server => $connection) {
            try {
                $admin = DB::connection($connection)
                    ->table('a27dmins')
                    ->whereRaw('BINARY Name = ?', [$nickname])
                    ->first(['Adm']);

                if ($admin && ($admin->Adm ?? 0) >= 7) {
                    $available[] = [
                        'id' => $server,
                        'name' => self::SERVER_NAMES[$server],
                        'level' => $admin->Adm,
                    ];
                }
            } catch (\Exception $e) {
            }
        }

        return $available;
    }

    public function hasAccessToServer(string $nickname, string $server): bool
    {
        $connection = $this->conn($server);
        if (! $connection) {
            return false;
        }

        try {
            $admin = DB::connection($connection)
                ->table('a27dmins')
                ->whereRaw('BINARY Name = ?', [$nickname])
                ->first(['Adm']);

            return $admin && ($admin->Adm ?? 0) >= 7;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function searchPlayer(
        string $server,
        ?string $nickname = null,
        ?int $accountId = null
    ): array {
        $connection = $this->conn($server);
        if (! $connection) {
            return [];
        }

        $query = DB::connection($connection)->table('a27ccount');

        if ($nickname) {
            $query->whereRaw('BINARY Name = ?', [$nickname]);
        }
        if ($accountId) {
            $query->where('ID', $accountId);
        }

        if (! $nickname && ! $accountId) {
            return [];
        }

        return $query->limit(20)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->ID,
                'name' => $row->Name,
                'kills' => $row->Kills ?? 0,
                'deaths' => $row->Deaths ?? 0,
                'level' => $row->Level ?? 1,
                'donate_money' => $row->DonateMoney ?? 0,
                'registered_at' => $row->DateReg ?? null,
                'last_online' => $row->Online ?? null,
            ])
            ->toArray();
    }

    public function getPlayerStats(string $server, int $accountId): ?array
    {
        $connection = $this->conn($server);
        if (! $connection) {
            return null;
        }

        $player = DB::connection($connection)
            ->table('a27ccount')
            ->where('ID', $accountId)
            ->first();

        if (! $player) {
            return null;
        }

        return [
            'id' => $player->ID,
            'name' => $player->Name,
            'email' => $player->Email ?? null,
            'email_verified' => ($player->EmailVerified ?? 0) == 1,
            'registered_at' => $player->DateReg ?? null,
            'last_online' => $player->Online ?? null,
            'ip_last' => $player->IpLog ?? null,
            'ip_reg' => $player->IpReg ?? null,
            'level' => $player->Level ?? 1,
            'cash' => $player->Cash ?? 0,
            'kills' => $player->Kills ?? 0,
            'deaths' => $player->Deaths ?? 0,
            'kd' => ($player->Deaths ?? 0) > 0
                ? round(($player->Kills ?? 0) / $player->Deaths, 2)
                : $player->Kills ?? 0,
            'donate' => [
                'money' => $player->DonateMoney ?? 0,
            ],
            'gangwar' => [
                'grove' => $player->Grove ?? 0,
                'ballas' => $player->Ballas ?? 0,
                'vagos' => $player->Vagos ?? 0,
                'aztec' => $player->Aztec ?? 0,
            ],
            'rank' => $this->calculateRank($player->Kills ?? 0),
            'security' => [
                'td_pass_set' => ($player->TDPass ?? '-') !== '-',
                'vid_kod' => $player->vidkod ?? 0,
            ],
        ];
    }

    public function getReputationLogs(
        string $server,
        ?string $fromPlayer = null,
        ?string $toPlayer = null,
        int $limit = 100
    ): array {
        $connection = $this->conn($server);
        if (! $connection) {
            return [];
        }

        $query = DB::connection($connection)->table('reputation');

        if ($fromPlayer) {
            $query->where('A', $fromPlayer);
        }
        if ($toPlayer) {
            $query->where('B', $toPlayer);
        }
        $banList = $this->getBanList($connection);

        return $query->orderByDesc('ID')
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($banList) {
                $date = $row->Date ?? null;

                return [
                    'id' => $row->ID ?? null,
                    'from' => $row->A,
                    'from_is_banned' => isset($banList[$row->A]),
                    'to' => $row->B,
                    'to_is_banned' => isset($banList[$row->B]),
                    'type' => $row->Repa, // + or -
                    'comment' => $row->Comment ?: null,
                    'date' => $date,
                ];
            })
            ->toArray();
    }

    public function getNicknameLogs(
        string $server,
        ?int $accountId = null,
        ?string $oldNick = null,
        ?string $newNick = null,
        int $limit = 100
    ): array {
        $connection = $this->conn($server);
        if (! $connection) {
            return [];
        }

        $query = DB::connection($connection)->table('names');

        if ($accountId) {
            $query->where('idacc', $accountId);
        }
        if ($oldNick) {
            $query->where('Do', $oldNick);
        }
        if ($newNick) {
            $query->where('Posle', $newNick);
        }

        return $query->orderByDesc('Date')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->ID ?? null,
                'account_id' => $row->idacc ?? null,
                'old_nick' => $row->Do,
                'new_nick' => $row->Posle,
                'approved_by' => $row->Adm ?? null,
                'date' => $row->Date,
            ])
            ->toArray();
    }

    public function getUnbanLogs(
        string $server,
        ?string $playerName = null,
        int $limit = 50
    ): array {
        $connection = $this->conn($server);
        if (! $connection) {
            return [];
        }

        $query = DB::connection($connection)->table('unbans');

        if ($playerName) {
            $query->where('UnBan', $playerName);
        }

        return $query->orderByDesc('Date')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->ID ?? null,
                'name' => $row->UnBan,
                'date' => $row->Date,
            ])
            ->toArray();
    }

    public function getPermanentBans(
        string $server,
        ?string $playerName = null,
        ?string $adminName = null,
        int $limit = 50
    ): array {
        $connection = $this->conn($server);
        if (! $connection) {
            return [];
        }

        // banlist Type = 4 is /block
        $query = DB::connection($connection)
            ->table('banlist')
            ->where('Type', 4);

        if ($playerName) {
            $query->where('Name', 'like', '%'.$this->escapeLike($playerName).'%');
        }
        if ($adminName) {
            $query->where('Admin', $adminName);
        }

        return $query->orderByDesc('UnBanDate')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->ID ?? null,
                'admin' => $row->Admin ?? null,
                'admin_ip' => $row->IPa ?? null,
                'name' => $row->Name,
                'player_ip' => $row->IPp ?? null,
                'reason' => $row->Reason ?? null,
                'date' => $row->Date ?? null,
            ])
            ->toArray();
    }

    public function getPermanentIPBans(
        string $server,
        ?string $ip = null,
        ?string $adminName = null,
        int $limit = 50
    ): array {
        $connection = $this->conn($server);
        if (! $connection) {
            return [];
        }

        $query = DB::connection($connection)->table('ip2banlist');

        // type=5 filter only when searching
        $hasFilters = $ip || $adminName;
        if ($hasFilters) {
            $query->where('type', 5);
        }

        if ($ip) {
            $query->where('IPp', $ip);
        }
        if ($adminName) {
            $query->where('Admin', $adminName);
        }

        return $query->orderByDesc('Date')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->ID ?? null,
                'admin' => $row->Admin,
                'admin_ip' => $row->IPa ?? null,
                'banned_ip' => $row->IPp,
                'date' => $row->Date,
            ])
            ->toArray();
    }

    public function getMatchmakingStats(
        string $server,
        ?string $playerName = null,
        int $limit = 100
    ): array {
        $connection = $this->conn($server);
        if (! $connection) {
            return [];
        }

        $query = DB::connection($connection)->table('Matchmaking');

        if ($playerName) {
            $query->where('Name', $playerName);
        }

        return $query->orderByDesc('ELO')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->ID ?? null,
                'name' => $row->Name,
                'cash' => $row->Cash ?? 0,
                'elo' => $row->ELO ?? 0,
                'kills' => $row->Kills ?? 0,
                'deaths' => $row->Deaths ?? 0,
                'games' => $row->Games ?? 0,
                'wins' => $row->Wins ?? 0,
                'mvp' => $row->MVP ?? 0,
                'winrate' => ($row->Games ?? 0) > 0
                    ? round((($row->Wins ?? 0) / $row->Games) * 100, 1)
                    : 0,
            ])
            ->toArray();
    }

    private function getBanList(string $connection): array
    {
        $bans = DB::connection($connection)
            ->table('banlist')
            ->pluck('Name')
            ->toArray();

        return array_flip($bans);
    }

    public function getMoneyTransferLogs(
        string $server,
        ?int $fromId = null,
        ?string $fromName = null,
        ?int $toId = null,
        ?string $toName = null,
        int $limit = 100
    ): array {
        $connection = $this->conn($server);
        if (! $connection) {
            return [];
        }

        $query = DB::connection($connection)->table('money_log');

        if ($fromId) {
            $query->where('user_id', $fromId);
        }
        if ($fromName) {
            $query->where('user_name', $fromName);
        }
        if ($toId) {
            $query->where('target_id', $toId);
        }
        if ($toName) {
            $query->where('target_name', $toName);
        }

        $banList = $this->getBanList($connection);

        return $query->orderByDesc('ID')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->ID ?? null,
                'from_id' => $row->user_id,
                'from_name' => $row->user_name,
                'from_is_banned' => isset($banList[$row->user_name]),
                'to_id' => $row->target_id,
                'to_name' => $row->target_name,
                'to_is_banned' => isset($banList[$row->target_name]),
                'amount' => $row->value ?? 0,
                'date' => $row->date,
            ])
            ->toArray();
    }

    public function getAccessoryLogs(
        string $server,
        ?int $accountId = null,
        ?string $accountName = null,
        ?string $accessoryName = null,
        int $limit = 100
    ): array {
        $connection = $this->conn($server);
        if (! $connection) {
            return [];
        }

        $query = DB::connection($connection)->table('logsAccessories');

        if ($accountId) {
            $query->where('accountID', $accountId);
        }
        if ($accountName) {
            $query->where('accountName', $accountName);
        }
        if ($accessoryName) {
            $query->where('accessoryName', 'like', '%'.$this->escapeLike($accessoryName).'%');
        }

        return $query->orderByDesc('actionDate')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->ID ?? null,
                'account_id' => $row->accountID,
                'account_name' => $row->accountName,
                'account_ip' => $row->accountIP ?? null,
                'accessory_name' => $row->accessoryName,
                'action' => $row->actionName,
                'date' => $row->actionDate,
            ])
            ->toArray();
    }

    public function updateBanReason(string $server, int $banId, string $reason): bool
    {
        $connection = $this->conn($server);
        if (! $connection) {
            return false;
        }

        $affected = DB::connection($connection)
            ->table('banlist')
            ->where('ID', $banId)
            ->where('Type', 4)
            ->update(['Reason' => $reason]);

        return $affected > 0;
    }

    public function getBanById(string $server, int $banId): ?array
    {
        $connection = $this->conn($server);
        if (! $connection) {
            return null;
        }

        $ban = DB::connection($connection)
            ->table('banlist')
            ->where('ID', $banId)
            ->where('Type', 4)
            ->first();

        if (! $ban) {
            return null;
        }

        return [
            'id' => $ban->ID,
            'name' => $ban->Name,
            'reason' => $ban->Reason,
        ];
    }

    private function calculateRank(int $kills): string
    {
        // 100k kills for "legend". at 1 kill per second that's 27 hours of pure murder.
        // some people need different hobbies. or maybe this IS the hobby. respect.
        return match (true) {
            $kills >= 100000 => 'legend',
            $kills >= 80000 => 'matchless',
            $kills >= 60000 => 'immortal',
            $kills >= 40000 => 'monster',
            $kills >= 20000 => 'invincible',
            $kills >= 15000 => 'fearless',
            $kills >= 10000 => 'professional',
            $kills >= 8000 => 'master',
            $kills >= 6000 => 'experienced',
            $kills >= 4000 => 'advanced',
            $kills >= 2000 => 'settled',
            $kills >= 1000 => 'amateur',
            $kills >= 500 => 'beginner',
            $kills >= 200 => 'newbie',
            default => 'newborn',
        };
    }
}
