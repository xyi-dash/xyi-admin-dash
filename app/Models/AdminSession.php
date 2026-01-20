<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'server',
        'unlocked_at',
        'last_activity_at',
        'ip_address',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(int $ttlMinutes = 60): bool
    {
        return $this->last_activity_at->addMinutes($ttlMinutes)->isPast();
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForServer($query, string $server)
    {
        return $query->where('server', $server);
    }

    public function scopeExpired($query, int $ttlMinutes = 60)
    {
        return $query->where('last_activity_at', '<', now()->subMinutes($ttlMinutes));
    }
}
