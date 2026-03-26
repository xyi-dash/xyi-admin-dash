<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminBan extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'admin_name',
        'server',
        'reason',
        'evidence',
        'banned_by',
    ];

    protected $casts = [
        'banned_at' => 'datetime',
    ];

    /**
     * Scope to filter by admin
     */
    public function scopeByAdmin($query, int $adminId, string $server)
    {
        return $query->where('admin_id', $adminId)->where('server', $server);
    }

    /**
     * Scope to filter by admin name
     */
    public function scopeByAdminName($query, string $adminName)
    {
        return $query->where('admin_name', $adminName);
    }

    /**
     * Check if an admin is banned
     */
    public static function isAdminBanned(int $adminId, string $server): bool
    {
        return self::byAdmin($adminId, $server)->exists();
    }

    /**
     * Check if an admin is banned by name
     */
    public static function isAdminBannedByName(string $adminName): bool
    {
        return self::byAdminName($adminName)->exists();
    }
}
