<?php

namespace App\Http\Middleware;

use App\Models\ControlPanelUser;
use App\Models\User;
use App\Services\ActionLogService;
use App\Services\AdminSessionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ControlPanelAuth
{
    public function __construct(
        private AdminSessionService $adminSession
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // token-based login from vue app
        if ($request->query('t')) {
            try {
                $token = base64_decode($request->query('t'));
                $data = decrypt($token);
                [$userId, $server, $timestamp] = explode('|', $data);
                
                if (time() - $timestamp < 300) {
                    $user = User::find($userId);
                    if ($user) {
                        Auth::login($user, true);
                        session()->save();
                        return redirect('/cp');
                    }
                }
            } catch (\Exception $e) {
                // bad token 👎👎👎
            }
        }
        
        $user = Auth::user();
        
        if (!$user) {
            return redirect('/login');
        }

        // must have at least one server unlocked (admin password entered)
        if (!$this->adminSession->hasAnyUnlocked($user)) {
            return redirect('/admin/login')
                ->with('error', 'unlock a server first. reimu demands tribute.');
        }

        // must be on control_panel_users list
        if (!ControlPanelUser::hasAccess($user->game_account_name, $user->server)) {
            abort(403, 'hakurei barrier: control panel access denied. you need explicit permission from the shrine maidens.');
        }

        $cpUser = ControlPanelUser::findByNickname($user->game_account_name, $user->server);
        $request->attributes->set('cp_user', $cpUser);

        if (!session('cp_logged')) {
            app(ActionLogService::class)->logCPLogin(
                $user->game_account_id,
                $user->game_account_name,
                $user->server,
                $request->ip()
            );
            session(['cp_logged' => true]);
        }

        return $next($request);
    }
}
