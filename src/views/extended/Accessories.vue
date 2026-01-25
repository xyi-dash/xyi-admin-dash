<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/service/api';

const authStore = useAuthStore();

const loading = ref(false);
const data = ref([]);
const filters = ref({
    account_name: '',
    accessory: ''
});

onMounted(async () => {
    await loadData();
});

async function loadData() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.account_name) params.append('account_name', filters.value.account_name);
        if (filters.value.accessory) params.append('accessory', filters.value.accessory);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/extended/accessories?${params}`);
        data.value = response.data.data || [];
    } catch {
        // same pattern as MoneyTransfers.vue, ctrl+c ctrl+v engineering at its finest
        console.warn('accessories said no');
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
            <div class="font-semibold text-xl">{{ $t('extended.accessories.title') }}</div>

            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-2">
                    <label for="name">{{ $t('extended.accessories.name') }}</label>
                    <InputText id="name" v-model="filters.account_name" :placeholder="$t('extended.accessories.name_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="accessory">{{ $t('extended.accessories.accessory') }}</label>
                    <InputText id="accessory" v-model="filters.accessory" :placeholder="$t('extended.accessories.accessory_placeholder')" @keyup.enter="search" />
                </div>
                <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
            </div>
        </div>

        <div class="card">
            <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
                <Column field="account_id" :header="$t('extended.accessories.account_id')" style="width: 100px">
                    <template #body="{ data }">
                        <span class="font-mono">{{ data.account_id }}</span>
                    </template>
                </Column>
                <Column field="account_name" :header="$t('extended.accessories.name')">
                    <template #body="{ data }">
                        <span class="font-semibold">{{ data.account_name }}</span>
                    </template>
                </Column>
                <Column field="accessory_name" :header="$t('extended.accessories.accessory')" />
                <Column field="action" :header="$t('extended.accessories.action')" style="width: 120px" />
                <Column field="account_ip" :header="$t('extended.accessories.ip')" style="width: 140px">
                    <template #body="{ data }">
                        <span class="font-mono text-muted-color">{{ data.account_ip }}</span>
                    </template>
                </Column>
                <Column field="date" :header="$t('extended.accessories.date')" style="width: 180px" />

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('extended.accessories.no_logs') }}</div>
                </template>
            </DataTable>
        </div>
    </Fluid>
</template>
