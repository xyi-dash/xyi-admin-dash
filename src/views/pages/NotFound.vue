<script setup>
import { redirectToDashboard } from '@/service/api';
</script>

<template>
    <div class="bg-surface-50 dark:bg-surface-950 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center">
            <div style="border-radius: 56px; padding: 0.3rem; background: linear-gradient(180deg, color-mix(in srgb, var(--primary-color), transparent 60%) 10%, var(--surface-ground) 30%)">
                <div class="w-full bg-surface-0 dark:bg-surface-900 py-20 px-8 sm:px-20 flex flex-col items-center" style="border-radius: 53px">
                    <span class="text-primary font-bold text-3xl">404</span>
                    <h1 class="text-surface-900 dark:text-surface-0 font-bold text-3xl lg:text-5xl mb-2">{{ $t('errors.not_found') }}</h1>
                    <div class="text-surface-600 dark:text-surface-200 mb-8 text-center">
                        {{ $t('errors.unknown') }}
                    </div>
                    <div class="flex gap-3">
                        <Button as="router-link" :label="$t('common.back')" to="/" />
                        <Button :label="$t('auth.unlock.back_dashboard')" severity="secondary" text @click="redirectToDashboard" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
