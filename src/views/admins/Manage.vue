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
    <div class="card">
        <div class="flex items-center gap-2 mb-4">
            <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
            <h5 class="m-0">{{ $t('manage.title') }}: {{ route.params.name }}</h5>
        </div>

        <ProgressSpinner v-if="loading" class="flex justify-center" />

        <div v-else-if="admin">
            <div class="card w-full max-w-2xl">
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-xl font-semibold">{{ admin.name }}</span>
                    <span class="w-2 h-2 rounded-full" :class="admin.is_online ? 'bg-green-500' : 'bg-red-400'" :title="admin.is_online ? $t('common.online') : $t('common.offline')"></span>
                    <Tag v-if="admin.is_support" severity="info" size="small">{{ $t('admins.support') }}</Tag>
                    <Tag v-if="admin.is_youtuber" severity="warn" size="small">{{ $t('admins.youtuber') }}</Tag>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <div class="text-muted-color text-sm mb-1">{{ $t('manage.level') }}</div>
                        <div>{{ admin.level }}{{ admin.is_ga ? '+' : '' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm mb-1">{{ $t('manage.warnings') }}</div>
                        <div :class="{ 'text-red-500': admin.warnings >= 2 }">{{ admin.warnings }}/3</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm mb-1">{{ $t('manage.appointed') }}</div>
                        <div>{{ admin.appointed_date || '-' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm mb-1">{{ $t('manage.appointed_by') }}</div>
                        <div>{{ admin.appointed_by || $t('common.unknown') }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm mb-1">{{ $t('manage.last_visit') }}</div>
                        <div>{{ admin.last_online || '-' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm mb-1">{{ $t('manage.confirmed') }}</div>
                        <div>{{ admin.needs_confirm ? $t('common.no') : $t('common.yes') }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm mb-1">{{ $t('manage.reg_ip') }}</div>
                        <div class="font-mono">{{ admin.ip_reg || '-' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm mb-1">{{ $t('manage.last_ip') }}</div>
                        <div class="font-mono">{{ admin.ip_last || '-' }}</div>
                    </div>
                </div>

                <Divider />

                <h6 class="mb-3">{{ $t('manage.online_time') }}</h6>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <div class="text-muted-color text-sm mb-1">{{ $t('manage.today') }}</div>
                        <div class="font-mono">{{ admin.playtime?.today || '00:00' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm mb-1">{{ $t('manage.yesterday') }}</div>
                        <div class="font-mono">{{ admin.playtime?.yesterday || '00:00' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm mb-1">{{ $t('manage.day_before') }}</div>
                        <div class="font-mono">{{ admin.playtime?.day_before || '00:00' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm mb-1">{{ $t('manage.this_week') }}</div>
                        <div class="font-mono">{{ admin.playtime?.week || '00:00' }}</div>
                    </div>
                </div>

                <template v-if="admin.stats">
                    <Divider />
                    <h6 class="mb-3">{{ $t('manage.admin_stats') }}</h6>
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <div class="text-muted-color text-sm mb-1">{{ $t('manage.hours_played') }}</div>
                            <div>{{ admin.stats.hours_played }}/{{ admin.stats.hours_required }}</div>
                        </div>
                        <div>
                            <div class="text-muted-color text-sm mb-1">{{ $t('manage.punishments') }}</div>
                            <div>{{ admin.stats.punishments }}/{{ admin.stats.punishments_required }}</div>
                        </div>
                        <div>
                            <div class="text-muted-color text-sm mb-1">{{ $t('manage.reports') }}</div>
                            <div>{{ admin.stats.reports }}/{{ admin.stats.reports_required }}</div>
                        </div>
                    </div>
                </template>

                <Divider />

                <h6 class="mb-3">{{ $t('manage.actions') }}</h6>
                <div v-if="actions.length" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-2">
                        <Select v-model="selectedAction" :options="actions" :placeholder="$t('manage.select_action')" class="w-full">
                            <template #value="{ value }">
                                {{ value ? getActionLabel(value) : $t('manage.select_action') }}
                            </template>
                            <template #option="{ option }">
                                {{ getActionLabel(option) }}
                            </template>
                        </Select>
                    </div>

                    <div v-if="selectedAction && actionsNeedingReason.includes(selectedAction)" class="flex flex-col gap-2">
                        <InputText v-model="reason" :placeholder="$t('manage.reason_placeholder')" class="w-full" />
                    </div>

                    <Button :label="$t('manage.execute')" :loading="executing" :disabled="!selectedAction" @click="executeAction" />
                </div>

                <div v-else class="text-center py-4">
                    <i class="pi pi-lock text-2xl text-muted-color mb-2"></i>
                    <p class="text-muted-color">{{ $t('manage.no_actions') }}</p>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-8">
            <i class="pi pi-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
            <p class="text-muted-color">{{ $t('manage.admin_not_found') }}</p>
        </div>
    </div>
</template>
