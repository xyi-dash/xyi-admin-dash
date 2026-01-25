<?php

namespace App\Providers;

use App\Livewire\LanguageSwitcher;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Livewire::component('language-switcher', LanguageSwitcher::class);

        $this->configureRateLimiters();
    }

    /**
     * nginx proxies everything through one IP so we cant trust $request->ip() for shit.
     * bind to user_id after auth, pray before auth.
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('authenticated', fn ($request) => $request->user()
            ? Limit::perMinute(60)->by($request->user()->id)
            : Limit::perMinute(10)->by($request->ip())
        );

        RateLimiter::for('login', fn ($request) => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('token-exchange', fn ($request) => Limit::perMinute(20)->by($request->ip()));

        RateLimiter::for('admin-auth', fn ($request) => $request->user()
            ? Limit::perMinute(10)->by($request->user()->id)
            : Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for('sensitive', fn ($request) => $request->user()
            ? Limit::perMinute(15)->by($request->user()->id)
            : Limit::perMinute(5)->by($request->ip())
        );
    }
}
