<?php

namespace App\Http\Middleware;

use App\Services\GameAccountService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function __construct(
        private GameAccountService $gameAccountService
    ) {}

    public function handle(Request $request, Closure $next, ?int $minLevel = 1): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $adminLevel = $this->gameAccountService->getAdminLevel(
            $user->server,
            $user->game_account_name
        );

        if ($adminLevel < $minLevel) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $request->attributes->set('admin_level', $adminLevel);

        return $next($request);
    }
}
