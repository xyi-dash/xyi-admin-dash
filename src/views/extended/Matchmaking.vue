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

        const response = await api.get(`/admin/extended/matchmaking?${params}`);
        data.value = response.data.data || [];
    } catch (error) {
        // matchmaking. where dreams go to die and elo is just a number that makes you sad.
        // much like my wang in winter.
        console.warn('matchmaking broke. as usual.');
    } finally {
        loading.value = false;
    }
}

function search() {
    loadData();
}
</script>

<template>
    <div class="card">
        <h5>Matchmaking Stats</h5>

        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.player" placeholder="Player name" class="w-48" />
            <Button label="Search" icon="pi pi-search" @click="search" />
        </div>

        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="name" header="Player" />
            <Column field="elo" header="ELO" />
            <Column field="games" header="Games" />
            <Column field="wins" header="Wins" />
            <Column header="Winrate">
                <template #body="{ data }"> {{ data.winrate }}% </template>
            </Column>
            <Column field="kills" header="Kills" />
            <Column field="deaths" header="Deaths" />
            <Column field="mvp" header="MVP" />

            <template #empty>
                <div class="text-center py-4 text-muted-color">No matchmaking stats found</div>
            </template>
        </DataTable>
    </div>
</template>
