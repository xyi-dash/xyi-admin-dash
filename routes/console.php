<?php

use App\Services\AdminSessionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// reimu sweeps the shrine regularly!
Schedule::call(function () {
    $deleted = app(AdminSessionService::class)->cleanupExpired();
    if ($deleted > 0) {
        logger()->info("admin sessions cleanup: {$deleted} expired sessions yeeted");
    }
})->everyFiveMinutes()->name('admin-sessions-cleanup')->withoutOverlapping();
