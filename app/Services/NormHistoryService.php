<?php

namespace App\Services;

use App\Models\ServerSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NormHistoryService
{
    // 17 minutes a day keeps unemployment away!
    private const DEFAULT_NORM = 17;

    private const SERVER_NAME_TO_NUMBER = [
        'one' => 1,
        'two' => 2,
        'three' => 3,
    ];

    public function getHistory(int $playerId, string $serverName, int $days = 30): array
    {
        $serverNumber = self::SERVER_NAME_TO_NUMBER[$serverName] ?? 1;

        try {
            $history = DB::connection('bot')
                ->table('online_log')
                ->where('player_id', $playerId)
                ->where('server', $serverNumber)
                ->where('date', '>=', now()->subDays($days)->toDateString())
                ->orderBy('date', 'asc')
                ->get(['date', 'online'])
                ->map(fn ($row) => [
                    'date' => $row->date,
                    'online' => (int) $row->online,
                ])
                ->toArray();

            $normPerDay = $this->getNormForServer($serverName);

            return [
                'history' => $this->fillMissingDays($history, $days),
                'norm_required' => $normPerDay, // minutes per day
            ];
        } catch (\Exception $e) {
            Log::warning('Bot DB connection failed: '.$e->getMessage());

            return [
                'history' => [],
                'norm_required' => self::DEFAULT_NORM,
                'error' => 'bot_db_unavailable',
            ];
        }
    }

    private function getNormForServer(string $serverName): int
    {
        $conversationId = ServerSetting::getAdminConversationId($serverName);

        if (! $conversationId) {
            $serverNumber = self::SERVER_NAME_TO_NUMBER[$serverName] ?? 1;

            return $this->getNormByServerNumber($serverNumber);
        }

        try {
            $norm = DB::connection('bot')
                ->table('conversations')
                ->where('peer_id', $conversationId)
                ->where('norma', '>', 0)
                ->value('norma');

            return $norm ?: self::DEFAULT_NORM;
        } catch (\Exception $e) {
            return self::DEFAULT_NORM;
        }
    }

    private function getNormByServerNumber(int $server): int
    {
        try {
            $norm = DB::connection('bot')
                ->table('conversations')
                ->where('server', $server)
                ->where('norma', '>', 0)
                ->value('norma');

            return $norm ?: self::DEFAULT_NORM;
        } catch (\Exception $e) {
            return self::DEFAULT_NORM;
        }
    }

    private function fillMissingDays(array $history, int $days): array
    {
        $indexed = collect($history)->keyBy('date');
        $result = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $result[] = [
                'date' => $date,
                'online' => $indexed->get($date)['online'] ?? 0,
            ];
        }

        return $result;
    }
}
