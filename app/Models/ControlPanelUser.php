<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlPanelUser extends Model
{
    protected $fillable = ['nickname', 'server', 'permissions', 'created_by'];

    protected $casts = ['permissions' => 'array'];

    public function hasPermission(string $permission): bool
    {
        if ($this->isRoot()) {
            return true;
        }
        $perms = $this->permissions ?? [];

        return in_array($permission, $perms) || in_array('*', $perms);
    }

    public function isRoot(): bool
    {
        // the chosen ones
        return in_array($this->nickname, ['WhiteCat', 'Exfil_Chidori']);
    }

    public static function findByNickname(string $nickname, string $server): ?self
    {
        return self::where('nickname', $nickname)->where('server', $server)->first();
    }

    public static function hasAccess(string $nickname, string $server): bool
    {
        return self::where('nickname', $nickname)->where('server', $server)->exists();
    }
}
