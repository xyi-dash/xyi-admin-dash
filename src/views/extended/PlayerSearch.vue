<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import api from '@/service/api';

const router = useRouter();
const authStore = useAuthStore();

const loading = ref(false);
const searched = ref(false);
const data = ref([]);
const filters = ref({
    nickname: '',
    account_id: null
});

async function search() {
    if (!filters.value.nickname && !filters.value.account_id) return;

    loading.value = true;
    searched.value = false;
    try {
        const params = new URLSearchParams();
        if (filters.value.nickname) params.append('nickname', filters.value.nickname);
        if (filters.value.account_id) params.append('account_id', filters.value.account_id);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/players/search?${params}`);
        data.value = response.data.data || [];
        searched.value = true;
    } catch {
        console.warn('player search hit a wall');
    } finally {
        loading.value = false;
    }
}

const viewPlayer = (id) => router.push({ name: 'extended-player-stats', params: { id } });
const goAdvanced = () => router.push({ name: 'extended-players-advanced' });
</script>

<template>
    <Fluid>
        <div class="card flex flex-col gap-4">
            <div class="flex justify-between items-center">
                <div class="font-semibold text-xl">{{ $t('extended.player_search.title') }}</div>
                <Button :label="$t('extended.player_search.advanced_search')" icon="pi pi-filter" text @click="goAdvanced" />
            </div>

            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-2">
                    <label for="nickname">{{ $t('extended.player_search.nickname') }}</label>
                    <InputText id="nickname" v-model="filters.nickname" :placeholder="$t('extended.player_search.nickname_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="account_id">{{ $t('extended.player_search.account_id') }}</label>
                    <InputNumber id="account_id" v-model="filters.account_id" :placeholder="$t('extended.player_search.id_placeholder')" :useGrouping="false" @keyup.enter="search" />
                </div>
                <Button :label="$t('common.search')" icon="pi pi-search" :loading="loading" @click="search" />
            </div>
        </div>

        <div class="card flex flex-col gap-4">
            <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
                <Column field="id" :header="$t('extended.player_search.id')" style="width: 120px">
                    <template #body="{ data }">
                        <span class="font-mono">{{ data.id }}</span>
                    </template>
                </Column>
                <Column field="name" :header="$t('extended.player_search.name')">
                    <template #body="{ data }">
                        <span class="font-semibold">{{ data.name }}</span>
                    </template>
                </Column>
                <Column field="level" :header="$t('extended.player_search.level')" style="width: 100px" />
                <Column field="kills" :header="$t('extended.player_search.kills')" style="width: 100px">
                    <template #body="{ data }">{{ data.kills?.toLocaleString() }}</template>
                </Column>
                <Column field="deaths" :header="$t('extended.player_search.deaths')" style="width: 100px">
                    <template #body="{ data }">{{ data.deaths?.toLocaleString() }}</template>
                </Column>
                <Column field="donate_money" :header="$t('extended.player_search.donate')" style="width: 100px">
                    <template #body="{ data }">
                        <span class="text-yellow-500">{{ data.donate_money || 0 }} ₽</span>
                    </template>
                </Column>
                <Column :header="$t('common.actions')" style="width: 120px">
                    <template #body="{ data }">
                        <Button :label="$t('common.open')" icon="pi pi-external-link" size="small" outlined @click="viewPlayer(data.id)" />
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-muted-color">
                        {{ searched ? $t('extended.player_search.no_players') : $t('extended.player_search.enter_search') }}
                    </div>
                </template>
            </DataTable>
        </div>
    </Fluid>
</template>
