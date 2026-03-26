<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminCard extends Model
{
    use HasFactory;
    protected $fillable = [
        'creator_id',
        'creator_name',
        'creator_server',
        'target_admin_name',
        'action_type',
        'reason',
        'evidence',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Scope to get only pending cards
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get cards requiring confirmation
     */
    public function scopeRequiresConfirmation($query)
    {
        return $query->where('status', 'requires_confirmation');
    }

    /**
     * Scope to filter by creator
     */
    public function scopeByCreator($query, int $creatorId, string $server)
    {
        return $query->where('creator_id', $creatorId)->where('creator_server', $server);
    }

    /**
     * Scope to filter by target admin
     */
    public function scopeByTarget($query, string $targetName)
    {
        return $query->where('target_admin_name', $targetName);
    }

    /**
     * Scope to filter by server
     */
    public function scopeByServer($query, string $server)
    {
        return $query->where('creator_server', $server);
    }

    /**
     * Check if card is processed (approved or rejected)
     */
    public function isProcessed(): bool
    {
        return in_array($this->status, ['approved', 'rejected']);
    }

    /**
     * Check if card is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if card requires confirmation
     */
    public function requiresConfirmation(): bool
    {
        return $this->status === 'requires_confirmation';
    }

    /**
     * Check if action is permanent ban
     */
    public function isPermanentBan(): bool
    {
        return $this->action_type === 'permanent_ban';
    }
}
