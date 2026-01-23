<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ServerStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected static ?string $pollingInterval = '30s';

    private const SERVER_CONNECTIONS = [
        'one' => 'gangwar',
        'two' => 'gangwar2',
        'three' => 'gangwar3',
    ];

    protected function getStats(): array
    {
        $stats = [];
        $totalOnline = 0;
        $totalAdmins = 0;

        foreach (self::SERVER_CONNECTIONS as $serverName => $connection) {
            try {
                $online = DB::connection($connection)
                    ->table('a27dmins')
                    ->where('online2', 1)
                    ->count();

                $total = DB::connection($connection)
                    ->table('a27dmins')
                    ->count();

                $totalOnline += $online;
                $totalAdmins += $total;

                $stats[] = Stat::make(__('cp.stats.server_'.$serverName), "{$online} / {$total}")
                    ->description(__('cp.stats.online_admins'))
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color($online > 0 ? 'success' : 'gray');
            } catch (\Throwable) {
                $stats[] = Stat::make(__('cp.stats.server_'.$serverName), '-')
                    ->description(__('cp.stats.unavailable'))
                    ->color('danger');
            }
        }

        array_unshift($stats, Stat::make(__('cp.stats.total_online'), "{$totalOnline} / {$totalAdmins}")
            ->description(__('cp.stats.all_servers'))
            ->descriptionIcon('heroicon-m-globe-alt')
            ->color('primary'));

        return $stats;
    }
}
