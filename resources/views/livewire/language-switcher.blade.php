<div class="flex items-center gap-1">
    <button
        wire:click="switchLocale('ru')"
        @class([
            'px-2 py-1 text-sm rounded transition-colors',
            'bg-primary-500 text-white' => $locale === 'ru',
            'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => $locale !== 'ru',
        ])
    >
        RU
    </button>
    <button
        wire:click="switchLocale('en')"
        @class([
            'px-2 py-1 text-sm rounded transition-colors',
            'bg-primary-500 text-white' => $locale === 'en',
            'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => $locale !== 'en',
        ])
    >
        EN
    </button>
</div>
