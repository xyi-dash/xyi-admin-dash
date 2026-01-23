<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'primevue/usetoast';
import api from '@/service/api';

const { t } = useI18n();
const authStore = useAuthStore();
const toast = useToast();

const loading = ref(false);
const data = ref([]);
const filters = ref({
    player: '',
    admin: ''
});

const editDialog = ref(false);
const saving = ref(false);
const editingBan = ref(null);
const editReason = ref('');

onMounted(async () => {
    await loadData();
});

async function loadData() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.player) params.append('player', filters.value.player);
        if (filters.value.admin) params.append('admin', filters.value.admin);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/extended/bans?${params}`);
        data.value = response.data.data || [];
    } catch {
        // nope
    } finally {
        loading.value = false;
    }
}

function search() {
    loadData();
}

function openEditDialog(ban) {
    editingBan.value = ban;
    editReason.value = ban.reason || '';
    editDialog.value = true;
}

async function saveReason() {
    if (!editingBan.value) return;

    saving.value = true;
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : '';
        await api.patch(`/admin/extended/bans/${editingBan.value.id}/reason${serverParam}`, {
            reason: editReason.value
        });

        toast.add({
            severity: 'success',
            summary: t('common.success'),
            detail: t('extended.bans.reason_updated'),
            life: 3000
        });

        editDialog.value = false;
        await loadData();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: error.response?.data?.message || t('common.failed_save'),
            life: 5000
        });
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="card">
        <h5>{{ $t('extended.bans.title') }}</h5>

        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.player" :placeholder="$t('extended.bans.player_placeholder')" class="w-40" />
            <InputText v-model="filters.admin" :placeholder="$t('extended.bans.admin_placeholder')" class="w-40" />
            <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
        </div>

        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="admin" :header="$t('extended.bans.admin')" />
            <Column field="admin_ip" :header="$t('extended.bans.admin_ip')" />
            <Column field="name" :header="$t('extended.bans.player')" />
            <Column field="player_ip" :header="$t('extended.bans.player_ip')" />
            <Column field="reason" :header="$t('extended.bans.reason')">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <span class="flex-1">{{ data.reason }}</span>
                        <Button icon="pi pi-pencil" text rounded size="small" @click="openEditDialog(data)" v-tooltip.top="$t('common.edit')" />
                    </div>
                </template>
            </Column>
            <Column field="date" :header="$t('extended.bans.date')" />

            <template #empty>
                <div class="text-center py-4 text-muted-color">{{ $t('extended.bans.no_bans') }}</div>
            </template>
        </DataTable>

        <Dialog v-model:visible="editDialog" :header="$t('extended.bans.edit_reason')" modal class="w-full max-w-lg">
            <div class="flex flex-col gap-4">
                <div v-if="editingBan" class="text-muted-color text-sm">
                    {{ $t('extended.bans.player') }}: <span class="font-semibold text-color">{{ editingBan.name }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="font-semibold">{{ $t('extended.bans.reason') }}</label>
                    <Textarea v-model="editReason" rows="3" class="w-full" :placeholder="$t('extended.bans.reason_placeholder')" />
                </div>
            </div>
            <template #footer>
                <Button :label="$t('common.cancel')" text @click="editDialog = false" />
                <Button :label="$t('common.save')" :loading="saving" @click="saveReason" />
            </template>
        </Dialog>
    </div>
</template>
