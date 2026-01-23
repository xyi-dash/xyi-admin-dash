<?php

namespace App\Http\Middleware;

use App\Services\AdminSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresUnlockedServer
{
    public function __construct(
        private AdminSessionService $adminSession
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'I am dick and wang! And who are you??'], 401);
        }

        $server = $request->input('server') ?? $user->server;

        if (! in_array($server, config('servers.available'))) {
            return response()->json([
                'error' => 'server_invalid',
                'message' => 'that server does not exist in gensokyo',
            ], 400);
        }

        if (! $this->adminSession->isUnlocked($user, $server)) {
            return response()->json([
                'error' => 'server_locked',
                'server' => $server,
                'message' => 'hakurei barrier requires admin password for this realm',
            ], 403);
        }

        $this->adminSession->touch($user, $server);

        return $next($request);
    }
}
