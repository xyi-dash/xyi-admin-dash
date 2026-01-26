<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { useLayout } from '@/layout/composables/layout';
import api from '@/service/api';

const { t, locale } = useI18n();
const authStore = useAuthStore();
const { isDarkTheme } = useLayout();

const admin = ref(null);
const loading = ref(true);
const normHistory = ref(null);
const chartData = ref(null);
const chartOptions = ref(null);

const serverDisplayName = computed(() => {
    const map = { one: '01', two: '02', three: '03' };
    return map[authStore.currentServer] || authStore.currentServer;
});

onMounted(async () => {
    await loadAdminData();
    await loadNormHistory();
});

async function loadAdminData() {
    loading.value = true;
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : '';
        const { data } = await api.get(`/admin/me${serverParam}`);
        admin.value = data.admin;
    } catch (error) {
        console.warn('failed to load admin data. the api giveth, the api taketh away.');
    } finally {
        loading.value = false;
    }
}

async function loadNormHistory() {
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : '';
        const { data } = await api.get(`/admin/me/norm-history${serverParam}`);
        normHistory.value = data;
        setupChart();
    } catch (error) {
        // chart will just be empty
        // life goes on
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
    // btw online is in seconds while norm_required is in minutes
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
                    const gradient = ctx.createLinearGradient(0, 0, 0, 180);
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
                    padding: 20,
                    font: { size: 11 }
                }
            },
            tooltip: {
                backgroundColor: isDarkTheme.value ? '#1f2937' : '#fff',
                titleColor: isDarkTheme.value ? '#fff' : '#1f2937',
                bodyColor: isDarkTheme.value ? '#d1d5db' : '#4b5563',
                borderColor: surfaceBorder,
                borderWidth: 1,
                padding: 12,
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
                    font: { size: 9 },
                    maxRotation: 45,
                    minRotation: 45
                },
                grid: { display: false }
            },
            y: {
                beginAtZero: true,
                suggestedMax: Math.max(...values, normLine) * 1.1,
                ticks: {
                    color: textMuted,
                    font: { size: 10 },
                    stepSize: 2
                },
                grid: {
                    color: surfaceBorder,
                    drawBorder: false
                }
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
                            <span>Server: {{ serverDisplayName }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Tag :severity="admin.is_confirmed ? 'success' : 'warn'" class="text-lg px-4 py-2"> {{ t('common.level') }} {{ admin.level }}{{ admin.is_ga ? '+' : '' }} </Tag>
                        <Tag v-if="admin.warnings > 0" severity="danger" class="text-lg px-4 py-2"> {{ admin.warnings }}/3 {{ t('home.warnings_count') }} </Tag>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 xl:col-span-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4" style="align-items: start">
                        <div class="card h-full">
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
                        <div class="card h-full">
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

                    <div class="card mt-4">
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
                </div>

                <div class="col-span-12 xl:col-span-4">
                    <div class="card h-full flex flex-col">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-chart-line text-primary text-xl"></i>
                                <span class="font-semibold text-lg">{{ t('charts.online_history') }}</span>
                            </div>
                        </div>

                        <div v-if="chartData" class="flex flex-col gap-4 flex-1">
                            <div class="flex gap-3">
                                <div class="flex-1 text-center p-3 rounded-lg" :class="weeklyMet ? 'bg-green-500/10' : 'bg-orange-500/10'">
                                    <div class="text-lg font-bold" :class="weeklyMet ? 'text-green-500' : 'text-orange-500'">
                                        {{ weeklyTotal }}{{ t('charts.hours_short') }}
                                    </div>
                                    <div class="text-xs text-muted-color">{{ t('charts.this_week') }}</div>
                                </div>
                                <div class="flex-1 text-center p-3 bg-surface-100 dark:bg-surface-800 rounded-lg">
                                    <div class="text-lg font-bold text-primary">{{ weeklyNorm }}{{ t('charts.hours_short') }}</div>
                                    <div class="text-xs text-muted-color">{{ t('charts.weekly_norm') }}</div>
                                </div>
                            </div>
                            <div class="flex-1 min-h-[220px]">
                                <Chart type="line" :data="chartData" :options="chartOptions" class="h-full" />
                            </div>
                        </div>

                        <div v-else class="flex flex-col items-center justify-center py-12 text-muted-color flex-1">
                            <i class="pi pi-chart-line text-4xl mb-2"></i>
                            <span>{{ t('charts.no_data') }}</span>
                        </div>
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
