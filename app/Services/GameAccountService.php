<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * the bridge between our world and the game databases
 * three servers, three connections, infinite suffering.
 * 
 * why is the table called a27ccount? because someone in 2015 thought
 * "lets add random numbers to prevent sql injection". it didn't.
 * but now we're stuck with it forever. thanks yall are wonderful people!
 */
class GameAccountService
{
    // the holy trinity
    private const SERVER_CONNECTIONS = [
        'one' => 'gangwar',
        'two' => 'gangwar2',
        'three' => 'gangwar3',
    ];

    public function authenticate(string $server, string $nickname, string $password): ?object
    {
        $connection = $this->getConnection($server);
        if (!$connection) {
            return null;
        }

        // yeah it's bad but that's what the game uses :skull:
        // md5 in big 2026. i know. i KNOW. don't blame me bro
        $passwordHash = md5($password);

        $account = DB::connection($connection)
            ->table('a27ccount')
            ->whereRaw('BINARY Name = ?', [$nickname])
            ->where('Pass', $passwordHash)
            ->first(['ID', 'Name', 'IpReg', 'IpLog', 'Email']);

        return $account;
    }

    public function getAccountByName(string $server, string $nickname): ?object
    {
        $connection = $this->getConnection($server);
        if (!$connection) {
            return null;
        }

        return DB::connection($connection)
            ->table('a27ccount')
            ->whereRaw('BINARY Name = ?', [$nickname])
            ->first();
    }

    public function getFullAccountData(string $server, int $accountId): ?object
    {
        $connection = $this->getConnection($server);
        if (!$connection) {
            return null;
        }

        return DB::connection($connection)
            ->table('a27ccount')
            ->where('ID', $accountId)
            ->first();
    }

    public function getAdminLevel(string $server, string $nickname): int
    {
        $connection = $this->getConnection($server);
        if (!$connection) {
            return 0;
        }

        $admin = DB::connection($connection)
            ->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$nickname])
            ->first(['Adm']);

        return $admin?->Adm ?? 0;
    }

    public function verifyAdminPassword(string $server, string $nickname, string $password): bool
    {
        $connection = $this->getConnection($server);
        if (!$connection) {
            return false;
        }

        $admin = DB::connection($connection)
            ->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$nickname])
            ->first(['Password', 'Salt']);

        if (!$admin || empty($admin->Password) || empty($admin->Salt)) {
            return false;
        }

        $hashed = strtoupper(hash('sha256', $password . $admin->Salt));
        
        return $admin->Password === $hashed;
    }

    public function getAdminList(string $server, int $viewerLevel, bool $viewerIsGA = false): array
    {
        $connection = $this->getConnection($server);
        if (!$connection) {
            return [];
        }

        $query = DB::connection($connection)->table('a27dmins');

        if ($viewerLevel === 6 && $viewerIsGA) {
            $query->where('Adm', '<=', 6)->where('GA', 0);
        } elseif ($viewerLevel >= 7) {
            $query->where('Adm', '<', $viewerLevel);
        } else {
            $query->where('Adm', '<=', 6);
        }

        return $query
            ->orderByDesc('Adm')
            ->orderBy('Name')
            ->get()
            ->map(fn($admin) => [
                'id' => $admin->ID,
                'name' => $admin->Name,
                'level' => $admin->Adm,
                'is_ga' => ($admin->GA ?? 0) == 1,
                'warnings' => $admin->Preds ?? 0,
                'is_online' => ($admin->online2 ?? 0) == 1,
                'is_support' => ($admin->is_support ?? 0) == 1,
                'is_youtuber' => ($admin->is_media ?? 0) == 1,
                'reputation' => [
                    'up' => $admin->rep_up ?? 0,
                    'down' => $admin->rep_down ?? 0,
                ],
                'playtime_3days' => $this->formatAdminPlaytime(
                    ($admin->Segodnya ?? 0) + ($admin->Vchera ?? 0) + ($admin->Pozavchera ?? 0)
                ),
                'playtime_week' => $this->formatAdminPlaytime($admin->NOnline ?? 0),
            ])
            ->toArray();
    }

    public function canViewAdminDetails(int $viewerLevel, bool $viewerIsGA): bool
    {
        return $viewerLevel >= 7 || ($viewerLevel === 6 && $viewerIsGA);
    }

    public function getAdminById(string $server, int $adminId): ?object
    {
        $connection = $this->getConnection($server);
        if (!$connection) {
            return null;
        }

        return DB::connection($connection)
            ->table('a27dmins')
            ->where('ID', $adminId)
            ->first();
    }

    public function getAdminByName(string $server, string $nickname): ?object
    {
        $connection = $this->getConnection($server);
        if (!$connection) {
            return null;
        }

        return DB::connection($connection)
            ->table('a27dmins')
            ->whereRaw('BINARY Name = ?', [$nickname])
            ->first();
    }

    public function getAdminWithAccount(string $server, string $nickname): ?object
    {
        $connection = $this->getConnection($server);
        if (!$connection) {
            return null;
        }

        return DB::connection($connection)
            ->table('a27dmins as adm')
            ->leftJoin('a27ccount as acc', 'adm.Name', '=', 'acc.Name')
            ->whereRaw('BINARY adm.Name = ?', [$nickname])
            ->select('adm.*', 'acc.IpReg', 'acc.IpLog')
            ->first();
    }

    private function formatAdminPlaytime(int $seconds): string
    {
        $hours = intval($seconds / 3600);
        $mins = intval(($seconds % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $mins);
    }

    public function createSessionToken(object $gameAccount, string $server): string
    {
        $user = User::firstOrCreate(
            [
                'game_account_id' => $gameAccount->ID,
                'server' => $server,
            ],
            [
                'game_account_name' => $gameAccount->Name,
            ]
        );

        $user->update(['game_account_name' => $gameAccount->Name]);

        $user->tokens()->delete();
        Auth::login($user);

        return $user->createToken('auth-token')->plainTextToken;
    }

    private function getConnection(string $server): ?string
    {
        return self::SERVER_CONNECTIONS[$server] ?? null;
    }
}

