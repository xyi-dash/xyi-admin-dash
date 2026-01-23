<?php

namespace App\Services;

use App\Models\AdminSession;
use App\Models\User;

class AdminSessionService
{
    private const SESSION_TTL_MINUTES = 60;

    public function unlock(User $user, string $server, ?string $ip = null): AdminSession
    {
        return AdminSession::updateOrCreate(
            ['user_id' => $user->id, 'server' => $server],
            [
                'unlocked_at' => now(),
                'last_activity_at' => now(),
                'ip_address' => $ip,
            ]
        );
    }

    public function isUnlocked(User $user, string $server): bool
    {
        $session = AdminSession::forUser($user->id)->forServer($server)->first();

        if (! $session) {
            return false;
        }

        if ($session->isExpired(self::SESSION_TTL_MINUTES)) {
            $session->delete();

            return false;
        }

        return true;
    }

    public function touch(User $user, string $server): void
    {
        AdminSession::forUser($user->id)
            ->forServer($server)
            ->update(['last_activity_at' => now()]);
    }

    public function getUnlockedServers(User $user): array
    {
        return AdminSession::forUser($user->id)
            ->get()
            ->reject(fn ($s) => $s->isExpired(self::SESSION_TTL_MINUTES))
            ->map(fn ($s) => [
                'server' => $s->server,
                'unlocked_at' => $s->unlocked_at->toIso8601String(),
                'last_activity' => $s->last_activity_at->toIso8601String(),
            ])
            ->values()
            ->toArray();
    }

    public function hasAnyUnlocked(User $user): bool
    {
        return AdminSession::forUser($user->id)
            ->where('last_activity_at', '>=', now()->subMinutes(self::SESSION_TTL_MINUTES))
            ->exists();
    }

    public function lockServer(User $user, string $server): void
    {
        AdminSession::forUser($user->id)->forServer($server)->delete();
    }

    public function lockAll(User $user): void
    {
        AdminSession::forUser($user->id)->delete();
    }

    public function cleanupExpired(): int
    {
        return AdminSession::expired(self::SESSION_TTL_MINUTES)->delete();
    }
}
