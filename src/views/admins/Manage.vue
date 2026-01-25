<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'primevue/usetoast';
import api from '@/service/api';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const loading = ref(true);
const executing = ref(false);
const admin = ref(null);
const actions = ref([]);
const selectedAction = ref('');
const reason = ref('');

// these actions need reasons. unlike my decisions to work in frontend. those need therapy.
const actionsNeedingReason = ['warn', 'unwarn', 'promote', 'demote', 'remove', 'give_ga', 'remove_ga'];

const getActionLabel = (action) => t(`manage.action_labels.${action}`);

onMounted(async () => {
    await loadAdminData();
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
                <div class="col-span-12" :class="admin.stats ? 'xl:col-span-8' : ''">
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

                            <Button :label="$t('manage.execute')" icon="pi pi-check" :loading="executing" :disabled="!selectedAction" @click="executeAction" />
                        </div>

                        <div v-else class="text-center py-8">
                            <i class="pi pi-lock text-4xl text-muted-color mb-4"></i>
                            <p class="text-muted-color">{{ $t('manage.no_actions') }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="admin.stats" class="col-span-12 xl:col-span-4">
                    <div class="card h-full">
                        <div class="font-semibold text-xl mb-4">{{ $t('manage.admin_stats') }}</div>
                        <ul class="list-none p-0 m-0">
                            <li class="flex items-center justify-between py-3 border-b border-surface-200 dark:border-surface-700">
                                <span class="text-muted-color">{{ $t('manage.hours_played') }}</span>
                                <span class="font-semibold">{{ admin.stats.hours_played }}/{{ admin.stats.hours_required }}</span>
                            </li>
                            <li class="flex items-center justify-between py-3 border-b border-surface-200 dark:border-surface-700">
                                <span class="text-muted-color">{{ $t('manage.punishments') }}</span>
                                <span class="font-semibold">{{ admin.stats.punishments }}/{{ admin.stats.punishments_required }}</span>
                            </li>
                            <li class="flex items-center justify-between py-3">
                                <span class="text-muted-color">{{ $t('manage.reports') }}</span>
                                <span class="font-semibold">{{ admin.stats.reports }}/{{ admin.stats.reports_required }}</span>
                            </li>
                        </ul>
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
