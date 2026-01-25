<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/service/api';

const authStore = useAuthStore();

const loading = ref(false);
const data = ref([]);
const filters = ref({
    player: ''
});

onMounted(async () => {
    await loadData();
});

async function loadData() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.player) params.append('player', filters.value.player);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/extended/unbans?${params}`);
        data.value = response.data.data || [];
    } catch {
        console.warn('unbans decided to take a sick day');
    } finally {
        loading.value = false;
    }
}

function search() {
    loadData();
}
</script>

<template>
    <Fluid>
        <div class="card flex flex-col gap-4">
            <div class="font-semibold text-xl">{{ $t('extended.unbans.title') }}</div>

            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-2">
                    <label for="player">{{ $t('extended.unbans.name') }}</label>
                    <InputText id="player" v-model="filters.player" :placeholder="$t('extended.unbans.player_placeholder')" @keyup.enter="search" />
                </div>
                <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
            </div>
        </div>

        <div class="card">
            <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
                <Column field="name" :header="$t('extended.unbans.name')">
                    <template #body="{ data }">
                        <span class="font-semibold">{{ data.name }}</span>
                    </template>
                </Column>
                <Column field="date" :header="$t('extended.unbans.date')" />

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('extended.unbans.no_logs') }}</div>
                </template>
            </DataTable>
        </div>
    </Fluid>
</template>
