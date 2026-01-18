<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'game_account_id',
        'game_account_name',
        'server',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    // filament wants this
    public function getNameAttribute(): string
    {
        return $this->game_account_name ?? 'reimu';
    }
}
