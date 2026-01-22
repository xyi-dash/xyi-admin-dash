<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import api from '@/service/api'

const { t } = useI18n()
const authStore = useAuthStore()
const admin = ref(null)
const loading = ref(true)

onMounted(async () => {
    await loadAdminData()
})

async function loadAdminData() {
    loading.value = true
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : ''
        const { data } = await api.get(`/admin/me${serverParam}`)
        admin.value = data.admin
    } catch (error) {
        console.warn('failed to load admin data. the api giveth, the api taketh away.')
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <ProgressSpinner v-if="loading" class="flex justify-center py-8" />
        
        <template v-else-if="admin">
            <div class="card">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold m-0 mb-2">{{ t('home.welcome', { name: authStore.user?.name }) }}</h2>
                        <div class="flex items-center gap-2 text-muted-color">
                            <i class="pi pi-server"></i>
                            <span>{{ t('home.server') }}: {{ authStore.currentServer }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Tag :severity="admin.is_confirmed ? 'success' : 'warn'" class="text-lg px-4 py-2">
                            {{ t('common.level') }} {{ admin.level }}{{ admin.is_ga ? '+' : '' }}
                        </Tag>
                        <Tag v-if="admin.warnings > 0" severity="danger" class="text-lg px-4 py-2">
                            {{ admin.warnings }}/3 {{ t('home.warnings_count') }}
                        </Tag>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" style="align-items: start">
                <div class="card">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="pi pi-user text-primary text-xl"></i>
                        <span class="font-semibold text-lg">{{ t('home.admin_info') }}</span>
                    </div>
                    <div class="flex flex-col gap-3">
                        <div class="flex justify-between items-center h-8 border-b border-surface-200 dark:border-surface-700">
                            <span class="text-muted-color">{{ t('home.appointed_by') }}</span>
                            <span class="font-medium">{{ admin.appointed_by || t('common.unknown') }}</span>
                        </div>
                        <div class="flex justify-between items-center h-8 border-b border-surface-200 dark:border-surface-700">
                            <span class="text-muted-color">{{ t('home.appointed_date') }}</span>
                            <span class="font-medium">{{ admin.appointed_date || t('common.unknown') }}</span>
                        </div>
                        <div class="flex justify-between items-center h-8 border-b border-surface-200 dark:border-surface-700">
                            <span class="text-muted-color">{{ t('home.last_online') }}</span>
                            <span class="font-medium">{{ admin.last_online || t('common.none') }}</span>
                        </div>
                        <div class="flex justify-between items-center h-8">
                            <span class="text-muted-color">{{ t('common.status') }}</span>
                            <Tag :severity="admin.is_confirmed ? 'success' : 'warn'" size="small">
                                {{ admin.is_confirmed ? t('home.confirmed') : t('home.unconfirmed') }}
                            </Tag>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="pi pi-clock text-blue-500 text-xl"></i>
                        <span class="font-semibold text-lg">{{ t('home.playtime') }}</span>
                    </div>
                    <div class="flex flex-col gap-3">
                        <div class="flex justify-between items-center h-8 border-b border-surface-200 dark:border-surface-700">
                            <span class="text-muted-color">{{ t('home.today') }}</span>
                            <span class="font-medium text-primary">{{ admin.playtime?.today || '00:00' }}</span>
                        </div>
                        <div class="flex justify-between items-center h-8 border-b border-surface-200 dark:border-surface-700">
                            <span class="text-muted-color">{{ t('home.yesterday') }}</span>
                            <span class="font-medium">{{ admin.playtime?.yesterday || '00:00' }}</span>
                        </div>
                        <div class="flex justify-between items-center h-8 border-b border-surface-200 dark:border-surface-700">
                            <span class="text-muted-color">{{ t('home.day_before') }}</span>
                            <span class="font-medium">{{ admin.playtime?.day_before || '00:00' }}</span>
                        </div>
                        <div class="flex justify-between items-center h-8">
                            <span class="text-muted-color">{{ t('home.this_week') }}</span>
                            <span class="font-medium text-green-500">{{ admin.playtime?.week || '00:00' }}</span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="card">
                <div class="flex items-center gap-2 mb-4">
                    <i class="pi pi-chart-bar text-purple-500 text-xl"></i>
                    <span class="font-semibold text-lg">{{ t('home.statistics') }}</span>
                </div>
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1 text-center p-4 bg-surface-100 dark:bg-surface-800 rounded-lg">
                        <div class="h-9 flex items-center justify-center text-3xl font-bold text-primary mb-2">{{ admin.stats?.hours_played || 0 }}</div>
                        <div class="text-muted-color text-sm">{{ t('home.hours_played') }}</div>
                    </div>
                    <div class="flex-1 text-center p-4 bg-surface-100 dark:bg-surface-800 rounded-lg">
                        <div class="h-9 flex items-center justify-center text-3xl font-bold text-orange-500 mb-2">{{ admin.stats?.punishments_given || 0 }}</div>
                        <div class="text-muted-color text-sm">{{ t('home.punishments') }}</div>
                    </div>
                    <div class="flex-1 text-center p-4 bg-surface-100 dark:bg-surface-800 rounded-lg">
                        <div class="h-9 flex items-center justify-center text-3xl font-bold text-blue-500 mb-2">{{ admin.stats?.reports_answered || 0 }}</div>
                        <div class="text-muted-color text-sm">{{ t('home.reports_answered') }}</div>
                    </div>
                    <div class="flex-1 text-center p-4 bg-surface-100 dark:bg-surface-800 rounded-lg">
                        <div class="h-9 flex items-center justify-center gap-2 mb-2">
                            <i class="pi pi-thumbs-up text-green-500"></i>
                            <span class="text-2xl font-bold text-green-500">{{ admin.reputation?.up || 0 }}</span>
                            <span class="text-muted-color">/</span>
                            <i class="pi pi-thumbs-down text-red-500"></i>
                            <span class="text-2xl font-bold text-red-500">{{ admin.reputation?.down || 0 }}</span>
                        </div>
                        <div class="text-muted-color text-sm">{{ t('home.reputation') }}</div>
                    </div>
                </div>
            </div>
        </template>
        
        <div v-else class="card text-center py-8">
            <i class="pi pi-exclamation-triangle text-5xl text-yellow-500 mb-4"></i>
            <h3 class="text-xl font-semibold mb-2">{{ t('common.failed_load') }}</h3>
            <p class="text-muted-color">{{ t('common.no_data') }}</p>
        </div>
    </div>
</template>
