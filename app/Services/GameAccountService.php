<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class GameAccountService
{
    private const SERVER_CONNECTIONS = [
        'one' => 'gangwar',
        'two' => 'gangwar2',
        'three' => 'gangwar3',
        'four' => 'gangwar4',
    ];

    public function authenticate(string $server, string $nickname, string $password): ?object
    {
        $connection = $this->getConnection($server);
        if (!$connection) {
            return null;
        }

        // yeah it's bad but that's what the game uses :skull:
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

        return $user->createToken('auth-token')->plainTextToken;
    }

    private function getConnection(string $server): ?string
    {
        return self::SERVER_CONNECTIONS[$server] ?? null;
    }
}

