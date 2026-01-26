<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ServerSetting extends Model
{
    protected $fillable = ['server', 'key', 'value'];

    public const KEY_ADMIN_CONVERSATION_ID = 'admin_conversation_id';

    public static function getValue(string $server, string $key, mixed $default = null): mixed
    {
        $cacheKey = "server_setting:{$server}:{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($server, $key, $default) {
            $setting = self::where('server', $server)->where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    public static function setValue(string $server, string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['server' => $server, 'key' => $key],
            ['value' => $value]
        );

        Cache::forget("server_setting:{$server}:{$key}");
    }

    public static function getAdminConversationId(string $server): ?string
    {
        return self::getValue($server, self::KEY_ADMIN_CONVERSATION_ID);
    }
}
