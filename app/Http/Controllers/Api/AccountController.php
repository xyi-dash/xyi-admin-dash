<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GameAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        private GameAccountService $gameAccountService
    ) {}

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $account = $this->gameAccountService->getFullAccountData(
            $user->server,
            $user->game_account_id
        );

        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        $adminLevel = $this->gameAccountService->getAdminLevel(
            $user->server,
            $user->game_account_name
        );

        return response()->json([
            'account' => $this->formatAccountData($account),
            'is_admin' => $adminLevel > 0,
            'admin_level' => $adminLevel,
        ]);
    }

    private function formatAccountData(object $account): array
    {
        return [
            'id' => $account->ID,
            'name' => $account->Name,
            'email' => $account->Email ?? null,
            'registered_at' => $account->DateReg ?? null,
            'last_online' => $account->Online ?? null,
            'is_online' => ($account->Online2 ?? 0) == 1,
            
            'stats' => [
                'kills' => $account->Kills ?? 0,
                'deaths' => $account->Deaths ?? 0,
                'rank' => $this->calculateRank($account->Kills ?? 0),
                'playtime_seconds' => $account->Time ?? 0,
                'playtime_formatted' => $this->formatPlaytime($account->Time ?? 0),
            ],
            
            'gangs' => [
                'grove' => $account->Grove ?? 0,
                'ballas' => $account->Ballas ?? 0,
                'vagos' => $account->Vagos ?? 0,
                'aztecas' => $account->Aztec ?? 0,
            ],
            
            'donate' => [
                'balance' => $account->DonateMoney ?? 0,
                'total_donated' => $account->DonateAll ?? 0,
                'has_premium' => ($account->Prime ?? 0) >= 1,
            ],
            
            'security' => [
                'has_2fa' => ($account->TDPass ?? '-') !== '-',
            ],
        ];
    }

    private function calculateRank(int $kills): array
    {
        // reimu approves this shi!
        $ranks = [
            [0, 199, '-', 200],
            [200, 499, 'Новичок', 500],
            [500, 999, 'Начинающий', 1000],
            [1000, 1999, 'Любитель', 2000],
            [2000, 3999, 'Освоившийся', 4000],
            [4000, 5999, 'Продвинутый', 6000],
            [6000, 7999, 'Опытный', 8000],
            [8000, 9999, 'Мастер', 10000],
            [10000, 14999, 'Профессионал', 15000],
            [15000, 19999, 'Бесстрашный', 20000],
            [20000, 39999, 'Непобедимый', 40000],
            [40000, 59999, 'Монстер', 60000],
            [60000, 79999, 'Бессмертный', 80000],
            [80000, 99999, 'Бесподобный', 100000],
            [100000, PHP_INT_MAX, 'Легенда', null],
        ];

        foreach ($ranks as [$min, $max, $name, $next]) {
            if ($kills >= $min && $kills <= $max) {
                return [
                    'name' => $name,
                    'kills_current' => $kills,
                    'kills_next' => $next,
                ];
            }
        }

        return ['name' => '-', 'kills_current' => $kills, 'kills_next' => 200];
    }

    private function formatPlaytime(int $seconds): string
    {
        if ($seconds < 3600) {
            return intval($seconds / 60) . 'м';
        }
        
        $hours = intval($seconds / 3600);
        $mins = intval(($seconds % 3600) / 60);
        
        return "{$hours}ч {$mins}м";
    }
}

