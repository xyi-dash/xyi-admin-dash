<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/service/api';

const authStore = useAuthStore();

const loading = ref(false);
const data = ref([]);
const filters = ref({
    ip: '',
    admin: ''
});

onMounted(async () => {
    await loadData();
});

async function loadData() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.ip) params.append('ip', filters.value.ip);
        if (filters.value.admin) params.append('admin', filters.value.admin);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/extended/ip-bans?${params}`);
        data.value = response.data.data || [];
    } catch {
        // every time this fails i add another year to my therapy estimate
        console.warn('ip bans ghosted us');
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
            <div class="font-semibold text-xl">{{ $t('extended.ip_bans.title') }}</div>

            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-2">
                    <label for="ip">{{ $t('extended.ip_bans.ip') }}</label>
                    <InputText id="ip" v-model="filters.ip" :placeholder="$t('extended.ip_bans.ip_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="admin">{{ $t('extended.ip_bans.admin') }}</label>
                    <InputText id="admin" v-model="filters.admin" :placeholder="$t('extended.ip_bans.admin_placeholder')" @keyup.enter="search" />
                </div>
                <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
            </div>
        </div>

        <div class="card">
            <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
                <Column field="banned_ip" :header="$t('extended.ip_bans.ip')">
                    <template #body="{ data }">
                        <span class="font-mono">{{ data.banned_ip }}</span>
                    </template>
                </Column>
                <Column field="admin" :header="$t('extended.ip_bans.admin')" />
                <Column field="admin_ip" :header="$t('extended.ip_bans.admin_ip')">
                    <template #body="{ data }">
                        <span class="font-mono text-muted-color">{{ data.admin_ip }}</span>
                    </template>
                </Column>
                <Column field="date" :header="$t('extended.ip_bans.date')" />

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('extended.ip_bans.no_bans') }}</div>
                </template>
            </DataTable>
        </div>
    </Fluid>
</template>
