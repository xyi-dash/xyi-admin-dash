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
        $this->locale = $locale;

        $this->js('window.location.reload()');
    }

    public function render()
    {
        return view('livewire.language-switcher');
    }
}
