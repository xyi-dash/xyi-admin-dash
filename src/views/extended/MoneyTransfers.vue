<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/service/api';

const authStore = useAuthStore();

const loading = ref(false);
const data = ref([]);
const filters = ref({
    from_name: '',
    to_name: ''
});

onMounted(async () => {
    await loadData();
});

async function loadData() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.from_name) params.append('from_name', filters.value.from_name);
        if (filters.value.to_name) params.append('to_name', filters.value.to_name);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/extended/money-transfers?${params}`);
        data.value = response.data.data || [];
    } catch {
        console.warn('money evaporated');
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
            <div class="font-semibold text-xl">{{ $t('extended.money.title') }}</div>

            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-2">
                    <label for="from">{{ $t('extended.money.from') }}</label>
                    <InputText id="from" v-model="filters.from_name" :placeholder="$t('extended.money.from_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="to">{{ $t('extended.money.to') }}</label>
                    <InputText id="to" v-model="filters.to_name" :placeholder="$t('extended.money.to_placeholder')" @keyup.enter="search" />
                </div>
                <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
            </div>
        </div>

        <div class="card">
            <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
                <Column :header="$t('extended.money.from')">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">{{ data.from_name }}</span>
                            <Tag v-if="data.from_is_banned" severity="danger" size="small">BAN</Tag>
                        </div>
                    </template>
                </Column>
                <Column :header="$t('extended.money.to')">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">{{ data.to_name }}</span>
                            <Tag v-if="data.to_is_banned" severity="danger" size="small">BAN</Tag>
                        </div>
                    </template>
                </Column>
                <Column :header="$t('extended.money.amount')" style="width: 150px">
                    <template #body="{ data }">
                        <span class="text-green-500 font-bold">${{ data.amount?.toLocaleString() }}</span>
                    </template>
                </Column>
                <Column field="date" :header="$t('extended.money.date')" style="width: 180px" />

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('extended.money.no_logs') }}</div>
                </template>
            </DataTable>
        </div>
    </Fluid>
</template>
