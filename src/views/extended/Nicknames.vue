<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/service/api';

const authStore = useAuthStore();

const loading = ref(false);
const data = ref([]);
const filters = ref({
    account_id: '',
    old_nick: '',
    new_nick: ''
});

onMounted(async () => {
    await loadData();
});

async function loadData() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.account_id) params.append('account_id', filters.value.account_id);
        if (filters.value.old_nick) params.append('old_nick', filters.value.old_nick);
        if (filters.value.new_nick) params.append('new_nick', filters.value.new_nick);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/extended/nicknames?${params}`);
        data.value = response.data.data || [];
    } catch (error) {
        // if you're here debugging this, check PlayerStats.vue first. trust me.
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
            <div class="font-semibold text-xl">{{ $t('extended.nicknames.title') }}</div>

            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-2">
                    <label for="account_id">{{ $t('extended.nicknames.account_id') }}</label>
                    <InputNumber id="account_id" v-model="filters.account_id" :placeholder="$t('extended.nicknames.account_id')" :useGrouping="false" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="old_nick">{{ $t('extended.nicknames.old_nick') }}</label>
                    <InputText id="old_nick" v-model="filters.old_nick" :placeholder="$t('extended.nicknames.old_nick')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="new_nick">{{ $t('extended.nicknames.new_nick') }}</label>
                    <InputText id="new_nick" v-model="filters.new_nick" :placeholder="$t('extended.nicknames.new_nick')" @keyup.enter="search" />
                </div>
                <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
            </div>
        </div>

        <div class="card">
            <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
                <Column field="account_id" :header="$t('extended.nicknames.account_id')" style="width: 120px">
                    <template #body="{ data }">
                        <span class="font-mono">{{ data.account_id }}</span>
                    </template>
                </Column>
                <Column field="old_nick" :header="$t('extended.nicknames.old_nick')">
                    <template #body="{ data }">
                        <span class="text-red-400">{{ data.old_nick }}</span>
                    </template>
                </Column>
                <Column field="new_nick" :header="$t('extended.nicknames.new_nick')">
                    <template #body="{ data }">
                        <span class="text-green-500">{{ data.new_nick }}</span>
                    </template>
                </Column>
                <Column field="approved_by" :header="$t('extended.nicknames.approved_by')" />
                <Column field="date" :header="$t('extended.nicknames.date')" />

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('extended.nicknames.no_logs') }}</div>
                </template>
            </DataTable>
        </div>
    </Fluid>
</template>
