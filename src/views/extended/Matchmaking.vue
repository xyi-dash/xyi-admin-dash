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
    } catch {
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

const getEloClass = (elo) => {
    if (elo >= 1500) return 'text-purple-500';
    if (elo >= 1200) return 'text-green-500';
    if (elo >= 1000) return 'text-yellow-500';
    return 'text-red-500';
};
</script>

<template>
    <Fluid>
        <div class="card flex flex-col gap-4">
            <div class="font-semibold text-xl">{{ $t('extended.matchmaking.title') }}</div>

            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-2">
                    <label for="player">{{ $t('extended.matchmaking.name') }}</label>
                    <InputText id="player" v-model="filters.player" :placeholder="$t('extended.matchmaking.player_placeholder')" @keyup.enter="search" />
                </div>
                <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
            </div>
        </div>

        <div class="card">
            <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
                <Column field="name" :header="$t('extended.matchmaking.name')">
                    <template #body="{ data }">
                        <span class="font-semibold">{{ data.name }}</span>
                    </template>
                </Column>
                <Column field="elo" :header="$t('extended.matchmaking.elo')" style="width: 100px">
                    <template #body="{ data }">
                        <span class="font-bold" :class="getEloClass(data.elo)">{{ data.elo }}</span>
                    </template>
                </Column>
                <Column field="games" :header="$t('extended.matchmaking.games')" style="width: 80px" />
                <Column field="wins" :header="$t('extended.matchmaking.wins')" style="width: 80px">
                    <template #body="{ data }">
                        <span class="text-green-500">{{ data.wins }}</span>
                    </template>
                </Column>
                <Column :header="$t('extended.matchmaking.winrate')" style="width: 100px">
                    <template #body="{ data }">
                        <span :class="{ 'text-green-500': data.winrate >= 50, 'text-red-500': data.winrate < 50 }">{{ data.winrate }}%</span>
                    </template>
                </Column>
                <Column field="kills" :header="$t('extended.matchmaking.kills')" style="width: 80px" />
                <Column field="deaths" :header="$t('extended.matchmaking.deaths')" style="width: 80px" />
                <Column field="mvp" :header="$t('extended.matchmaking.mvp')" style="width: 80px">
                    <template #body="{ data }">
                        <span class="text-yellow-500">{{ data.mvp }}</span>
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('extended.matchmaking.no_stats') }}</div>
                </template>
            </DataTable>
        </div>
    </Fluid>
</template>
