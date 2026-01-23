<?php

namespace App\Http\Controllers\Api\Traits;

use App\Services\GameAccountService;
use Illuminate\Http\Request;

trait ResolvesServer
{
    protected function resolveServer(Request $request, ?int $minLevel = null): ?string
    {
        $user = $request->user();
        $requested = $request->input('server');

        if (! $requested) {
            return $user->server;
        }

        if (! in_array($requested, config('servers.available'))) {
            return null;
        }

        $gameService = app(GameAccountService::class);
        $admin = $gameService->getAdminByName($requested, $user->game_account_name);

        if (! $admin) {
            return null; // not admin there
        }

        $level = $admin->Adm ?? 0;

        if ($minLevel !== null && $level < $minLevel) {
            return null; // level too low on that server
        }

        return $requested;
    }

    protected function getAdminLevelOnServer(Request $request, string $server): int
    {
        $user = $request->user();
        $gameService = app(GameAccountService::class);
        $admin = $gameService->getAdminByName($server, $user->game_account_name);

        return $admin ? ($admin->Adm ?? 0) : 0;
    }
}
