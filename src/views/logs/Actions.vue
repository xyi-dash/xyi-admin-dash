<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/service/api';

const authStore = useAuthStore();

const loading = ref(false);
const data = ref([]);
const page = ref(0);
const showKills = ref(false);
const filters = ref({
    admin: '',
    player: '',
    cmd: ''
});

onMounted(async () => {
    await loadData();
});

async function loadData() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.admin) params.append('admin', filters.value.admin);
        if (filters.value.player) params.append('player', filters.value.player);
        if (filters.value.cmd) params.append('cmd', filters.value.cmd);
        if (showKills.value) params.append('with_kills', '1');
        params.append('page', page.value);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/logs/actions?${params}`);
        data.value = response.data.data || [];
    } catch (error) {
        console.warn('actions log said no');
    } finally {
        loading.value = false;
    }
}

function search() {
    page.value = 0;
    loadData();
}

function prevPage() {
    if (page.value > 0) {
        page.value--;
        loadData();
    }
}

function nextPage() {
    page.value++;
    loadData();
}

function toggleKills() {
    showKills.value = !showKills.value;
    loadData();
}
</script>

<template>
    <div class="card">
        <h5>{{ $t('logs.actions.title') }}</h5>

        <div class="flex flex-wrap gap-2 mb-4 items-center">
            <InputText v-model="filters.admin" :placeholder="$t('logs.actions.admin_placeholder')" class="w-40" />
            <InputText v-model="filters.player" :placeholder="$t('logs.actions.player_placeholder')" class="w-40" />
            <InputText v-model="filters.cmd" :placeholder="$t('logs.actions.command_placeholder')" class="w-40" />
            <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
            <div class="flex items-center gap-2 ml-auto">
                <label class="text-sm text-muted-color cursor-pointer" @click="toggleKills">{{ $t('logs.actions.show_kills') }}</label>
                <InputSwitch v-model="showKills" @change="loadData" />
            </div>
        </div>

        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="admin" :header="$t('logs.actions.admin')" />
            <Column field="player" :header="$t('logs.actions.player')">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <span>{{ data.player }}</span>
                        <Tag v-if="showKills && data.player_kills !== null" severity="secondary" size="small"> {{ data.player_kills?.toLocaleString() }} kills </Tag>
                    </div>
                </template>
            </Column>
            <Column field="cmd" :header="$t('logs.actions.command')" />
            <Column field="amount" :header="$t('logs.actions.amount')" />
            <Column field="reason" :header="$t('logs.actions.reason')" />
            <Column field="date" :header="$t('logs.actions.date')" />

            <template #empty>
                <div class="text-center py-4 text-muted-color">{{ $t('logs.actions.no_actions') }}</div>
            </template>
        </DataTable>

        <div class="flex justify-between items-center mt-4">
            <span class="text-muted-color">{{ $t('common.page') }} {{ page + 1 }}</span>
            <div class="flex gap-2">
                <Button icon="pi pi-chevron-left" text :disabled="page === 0" @click="prevPage" />
                <Button icon="pi pi-chevron-right" text @click="nextPage" />
            </div>
        </div>
    </div>
</template>
