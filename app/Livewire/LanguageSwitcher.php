<?php

namespace App\Livewire;

use Livewire\Component;

class LanguageSwitcher extends Component
{
    public string $locale;

    public function mount(): void
    {
        $this->locale = session('locale', 'ru');
    }

    public function switchLocale(string $locale): void
    {
        if (!in_array($locale, ['en', 'ru'])) {
            return;
        }

        session(['locale' => $locale]);
        app()->setLocale($locale);

        $this->redirect(request()->header('Referer', '/cp'));
    }

    public function render()
    {
        return view('livewire.language-switcher');
    }
}
