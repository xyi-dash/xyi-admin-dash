<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { useLayout } from '@/layout/composables/layout';
import { useToast } from 'primevue/usetoast';
import api from '@/service/api';

const { t, locale } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();
const { isDarkTheme } = useLayout();

const loading = ref(true);
const executing = ref(false);
const admin = ref(null);
const actions = ref([]);
const selectedAction = ref('');
const reason = ref('');

const normHistory = ref(null);
const chartData = ref(null);
const chartOptions = ref(null);

// these actions need reasons. unlike my decisions to work in frontend. those need therapy.
const actionsNeedingReason = ['warn', 'unwarn', 'promote', 'demote', 'remove', 'give_ga', 'remove_ga'];

const getActionLabel = (action) => t(`manage.action_labels.${action}`);

onMounted(async () => {
    await loadAdminData();
    await loadNormHistory();
});

async function loadAdminData() {
    loading.value = true;
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : '';
        const { data } = await api.get(`/admin/manage/${encodeURIComponent(route.params.name)}/actions${serverParam}`);
        admin.value = data.admin;
        actions.value = data.actions;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: t('common.failed_load'),
            life: 3000
        });
    } finally {
        loading.value = false;
    }
}

async function loadNormHistory() {
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : '';
        const { data } = await api.get(`/admin/manage/${encodeURIComponent(route.params.name)}/norm-history${serverParam}`);
        normHistory.value = data;
        setupChart();
    } catch (error) {
        // chart stays empty. such is life...
    }
}

function setupChart() {
    if (!normHistory.value?.history) return;

    const documentStyle = getComputedStyle(document.documentElement);
    const primaryColor = documentStyle.getPropertyValue('--p-primary-500') || '#10b981';
    const surfaceBorder = documentStyle.getPropertyValue('--surface-border') || '#404040';
    const textMuted = documentStyle.getPropertyValue('--text-color-secondary') || '#a1a1aa';

    const dateFormatter = new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'short' });
    const labels = normHistory.value.history.map((h) => {
        const date = new Date(h.date);
        return dateFormatter.format(date);
    });

    // same again online's in secs norm_required mins
    const values = normHistory.value.history.map((h) => Math.round((h.online / 3600) * 10) / 10);
    const normLine = normHistory.value.norm_required / 60;

    chartData.value = {
        labels,
        datasets: [
            {
                label: t('charts.online_hours'),
                data: values,
                fill: true,
                borderColor: primaryColor,
                backgroundColor: (context) => {
                    const ctx = context.chart.ctx;
                    const gradient = ctx.createLinearGradient(0, 0, 0, 140);
                    gradient.addColorStop(0, `${primaryColor}50`);
                    gradient.addColorStop(1, `${primaryColor}05`);
                    return gradient;
                },
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointBackgroundColor: primaryColor,
                borderWidth: 2
            },
            {
                label: t('charts.norm'),
                data: Array(labels.length).fill(normLine),
                borderColor: '#ef4444',
                borderDash: [6, 4],
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 0,
                fill: false
            }
        ]
    };

    chartOptions.value = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index'
        },
        layout: {
            padding: { top: 4, bottom: 4 }
        },
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: {
                    color: textMuted,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 16,
                    font: { size: 10 }
                }
            },
            tooltip: {
                backgroundColor: isDarkTheme.value ? '#1f2937' : '#fff',
                titleColor: isDarkTheme.value ? '#fff' : '#1f2937',
                bodyColor: isDarkTheme.value ? '#d1d5db' : '#4b5563',
                borderColor: surfaceBorder,
                borderWidth: 1,
                padding: 10,
                callbacks: {
                    label: (ctx) => {
                        const val = Math.round(ctx.raw * 10) / 10;
                        return ` ${ctx.dataset.label}: ${val}${t('charts.hours_short')}`;
                    }
                }
            }
        },
        scales: {
            x: {
                ticks: {
                    color: textMuted,
                    font: { size: 8 },
                    maxRotation: 45,
                    minRotation: 45
                },
                grid: { display: false }
            },
            y: {
                beginAtZero: true,
                suggestedMax: Math.max(...values, normLine) * 1.1,
                ticks: { color: textMuted, font: { size: 10 }, stepSize: 2 },
                grid: { color: surfaceBorder, drawBorder: false }
            }
        }
    };
}

watch([isDarkTheme, locale], () => setupChart());

const weeklyTotal = computed(() => {
    if (!normHistory.value?.history) return 0;
    
    const today = new Date();
    const dayOfWeek = today.getDay(); // 0=sun, 1=mon...
    const daysSinceMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
    const monday = new Date(today);
    monday.setDate(today.getDate() - daysSinceMonday);
    monday.setHours(0, 0, 0, 0);
    
    const thisWeek = normHistory.value.history.filter((h) => {
        const date = new Date(h.date);
        return date >= monday;
    });
    
    const total = thisWeek.reduce((sum, h) => sum + h.online, 0);
    return Math.round((total / 3600) * 10) / 10;
});

const weeklyNorm = computed(() => {
    if (!normHistory.value?.norm_required) return 0;
    return Math.round((normHistory.value.norm_required * 7 / 60) * 10) / 10;
});

const weeklyMet = computed(() => {
    return weeklyTotal.value >= weeklyNorm.value;
});

async function executeAction() {
    if (!selectedAction.value) return;

    const needsReason = actionsNeedingReason.includes(selectedAction.value);
    if (needsReason && !reason.value.trim()) {
        toast.add({
            severity: 'warn',
            summary: t('common.warning'),
            detail: t('manage.reason_required'),
            life: 3000
        });
        return;
    }

    executing.value = true;
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : '';
        const { data } = await api.post(`/admin/manage${serverParam}`, {
            target_name: admin.value.name,
            action: selectedAction.value,
            reason: reason.value || ' '
        });

        toast.add({
            severity: 'success',
            summary: t('common.success'),
            detail: t('manage.success'),
            life: 3000
        });

        admin.value = data.admin;
        selectedAction.value = '';
        reason.value = '';
        await loadAdminData();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: error.response?.data?.message || t('manage.failed'),
            life: 5000
        });
    } finally {
        executing.value = false;
    }
}

function goBack() {
    router.push({ name: 'admins' });
}
</script>

<template>
    <div class="flex flex-col gap-8">
        <div class="card">
            <div class="flex items-center gap-2">
                <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
                <div class="font-semibold text-xl">{{ $t('manage.title') }}: {{ route.params.name }}</div>
            </div>
        </div>

        <ProgressSpinner v-if="loading" class="flex justify-center py-8" />

        <template v-else-if="admin">
            <div class="grid grid-cols-12 gap-8">
                <div class="col-span-12 xl:col-span-8">
                    <div class="card h-full">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="text-2xl font-semibold">{{ admin.name }}</span>
                            <span class="w-3 h-3 rounded-full" :class="admin.is_online ? 'bg-green-500' : 'bg-red-400'"></span>
                            <Tag v-if="admin.is_support" severity="info" size="small">{{ $t('admins.support') }}</Tag>
                            <Tag v-if="admin.is_youtuber" severity="warn" size="small">{{ $t('admins.youtuber') }}</Tag>
                            <Tag v-if="admin.is_ga" severity="success" size="small">GA</Tag>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                            <div>
                                <div class="text-muted-color text-sm mb-1">{{ $t('manage.level') }}</div>
                                <div class="font-semibold text-lg">{{ admin.level }}{{ admin.is_ga ? '+' : '' }}</div>
                            </div>
                            <div>
                                <div class="text-muted-color text-sm mb-1">{{ $t('manage.warnings') }}</div>
                                <div class="font-semibold text-lg" :class="{ 'text-red-500': admin.warnings >= 2 }">{{ admin.warnings }}/3</div>
                            </div>
                            <div>
                                <div class="text-muted-color text-sm mb-1">{{ $t('manage.appointed') }}</div>
                                <div class="font-semibold">{{ admin.appointed_date || '—' }}</div>
                            </div>
                            <div>
                                <div class="text-muted-color text-sm mb-1">{{ $t('manage.appointed_by') }}</div>
                                <div class="font-semibold">{{ admin.appointed_by || $t('common.unknown') }}</div>
                            </div>
                            <div>
                                <div class="text-muted-color text-sm mb-1">{{ $t('manage.last_visit') }}</div>
                                <div class="font-semibold">{{ admin.last_online || '—' }}</div>
                            </div>
                            <div>
                                <div class="text-muted-color text-sm mb-1">{{ $t('manage.confirmed') }}</div>
                                <Tag :severity="admin.needs_confirm ? 'warn' : 'success'" size="small">
                                    {{ admin.needs_confirm ? $t('common.no') : $t('common.yes') }}
                                </Tag>
                            </div>
                            <div>
                                <div class="text-muted-color text-sm mb-1">{{ $t('manage.reg_ip') }}</div>
                                <div class="font-mono text-sm">{{ admin.ip_reg || '—' }}</div>
                            </div>
                            <div>
                                <div class="text-muted-color text-sm mb-1">{{ $t('manage.last_ip') }}</div>
                                <div class="font-mono text-sm">{{ admin.ip_last || '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 xl:col-span-4">
                    <div class="card h-full">
                        <div class="font-semibold text-xl mb-4">{{ $t('manage.online_time') }}</div>
                        <ul class="list-none p-0 m-0">
                            <li class="flex items-center justify-between py-3 border-b border-surface-200 dark:border-surface-700">
                                <span class="text-muted-color">{{ $t('manage.today') }}</span>
                                <span class="font-mono font-semibold text-primary">{{ admin.playtime?.today || '00:00' }}</span>
                            </li>
                            <li class="flex items-center justify-between py-3 border-b border-surface-200 dark:border-surface-700">
                                <span class="text-muted-color">{{ $t('manage.yesterday') }}</span>
                                <span class="font-mono font-semibold">{{ admin.playtime?.yesterday || '00:00' }}</span>
                            </li>
                            <li class="flex items-center justify-between py-3 border-b border-surface-200 dark:border-surface-700">
                                <span class="text-muted-color">{{ $t('manage.day_before') }}</span>
                                <span class="font-mono font-semibold">{{ admin.playtime?.day_before || '00:00' }}</span>
                            </li>
                            <li class="flex items-center justify-between py-3">
                                <span class="text-muted-color">{{ $t('manage.this_week') }}</span>
                                <span class="font-mono font-semibold text-green-500">{{ admin.playtime?.week || '00:00' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-8">
                <div class="col-span-12 xl:col-span-8">
                    <div class="card h-full">
                        <div class="font-semibold text-xl mb-4">{{ $t('manage.actions') }}</div>

                        <div v-if="actions.length" class="flex flex-col gap-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-2">
                                    <label for="action">{{ $t('manage.select_action') }}</label>
                                    <Select id="action" v-model="selectedAction" :options="actions" :placeholder="$t('manage.select_action')" class="w-full">
                                        <template #value="{ value }">
                                            {{ value ? getActionLabel(value) : $t('manage.select_action') }}
                                        </template>
                                        <template #option="{ option }">
                                            {{ getActionLabel(option) }}
                                        </template>
                                    </Select>
                                </div>

                                <div v-if="selectedAction && actionsNeedingReason.includes(selectedAction)" class="flex flex-col gap-2">
                                    <label for="reason">{{ $t('common.reason') }}</label>
                                    <InputText id="reason" v-model="reason" :placeholder="$t('manage.reason_placeholder')" class="w-full" />
                                </div>
                            </div>

                            <Button :label="$t('manage.execute')" icon="pi pi-check" :loading="executing" :disabled="!selectedAction" class="w-auto self-start" @click="executeAction" />
                        </div>

                        <div v-else class="text-center py-8">
                            <i class="pi pi-lock text-4xl text-muted-color mb-4"></i>
                            <p class="text-muted-color">{{ $t('manage.no_actions') }}</p>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 xl:col-span-4">
                    <div class="card h-full flex flex-col">
                        <div class="font-semibold text-xl mb-4">{{ $t('charts.online_history') }}</div>

                        <div v-if="chartData" class="flex flex-col gap-4 flex-1">
                            <div class="flex gap-3">
                                <div class="flex-1 text-center p-3 rounded-lg" :class="weeklyMet ? 'bg-green-500/10' : 'bg-orange-500/10'">
                                    <div class="text-lg font-bold" :class="weeklyMet ? 'text-green-500' : 'text-orange-500'">
                                        {{ weeklyTotal }}{{ $t('charts.hours_short') }}
                                    </div>
                                    <div class="text-xs text-muted-color">{{ $t('charts.this_week') }}</div>
                                </div>
                                <div class="flex-1 text-center p-3 bg-surface-100 dark:bg-surface-800 rounded-lg">
                                    <div class="text-lg font-bold text-primary">{{ weeklyNorm }}{{ $t('charts.hours_short') }}</div>
                                    <div class="text-xs text-muted-color">{{ $t('charts.weekly_norm') }}</div>
                                </div>
                            </div>
                            <div class="flex-1 min-h-[180px]">
                                <Chart type="line" :data="chartData" :options="chartOptions" class="h-full" />
                            </div>
                        </div>

                        <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color flex-1">
                            <i class="pi pi-chart-line text-4xl mb-2"></i>
                            <span>{{ $t('charts.no_data') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="admin.stats" class="card">
                <div class="font-semibold text-xl mb-4">{{ $t('manage.admin_stats') }}</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="flex items-center justify-between p-4 bg-surface-100 dark:bg-surface-800 rounded-lg">
                        <div>
                            <div class="text-muted-color text-sm">{{ $t('manage.hours_played') }}</div>
                            <div class="text-2xl font-bold">{{ admin.stats.hours_played }}</div>
                        </div>
                        <div class="text-muted-color">/ {{ admin.stats.hours_required }}</div>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-surface-100 dark:bg-surface-800 rounded-lg">
                        <div>
                            <div class="text-muted-color text-sm">{{ $t('manage.punishments') }}</div>
                            <div class="text-2xl font-bold">{{ admin.stats.punishments }}</div>
                        </div>
                        <div class="text-muted-color">/ {{ admin.stats.punishments_required }}</div>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-surface-100 dark:bg-surface-800 rounded-lg">
                        <div>
                            <div class="text-muted-color text-sm">{{ $t('manage.reports') }}</div>
                            <div class="text-2xl font-bold">{{ admin.stats.reports }}</div>
                        </div>
                        <div class="text-muted-color">/ {{ admin.stats.reports_required }}</div>
                    </div>
                </div>
            </div>
        </template>

        <div v-else class="card text-center py-8">
            <i class="pi pi-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
            <p class="text-muted-color">{{ $t('manage.admin_not_found') }}</p>
        </div>
    </div>
</template>
