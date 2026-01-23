<?php

namespace App\Providers;

use App\Livewire\LanguageSwitcher;
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
    }
}
