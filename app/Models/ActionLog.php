<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'action_type',
        'actor_id',
        'actor_name',
        'actor_server',
        'target_id',
        'target_name',
        'target_server',
        'details',
        'ip_address',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function scopeByActor($query, int $actorId, string $server)
    {
        return $query->where('actor_id', $actorId)->where('actor_server', $server);
    }

    public function scopeByTarget($query, int $targetId, string $server)
    {
        return $query->where('target_id', $targetId)->where('target_server', $server);
    }

    public function scopeByPerson($query, int $personId, string $server)
    {
        return $query->where(function ($q) use ($personId, $server) {
            $q->where(fn ($q) => $q->where('actor_id', $personId)->where('actor_server', $server))
                ->orWhere(fn ($q) => $q->where('target_id', $personId)->where('target_server', $server));
        });
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('action_type', $type);
    }
}
