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
    <div class="card">
        <h5>Nickname Change Logs</h5>

        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.account_id" placeholder="Account ID" type="number" class="w-32" />
            <InputText v-model="filters.old_nick" placeholder="Old nickname" class="w-40" />
            <InputText v-model="filters.new_nick" placeholder="New nickname" class="w-40" />
            <Button label="Search" icon="pi pi-search" @click="search" />
        </div>

        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="account_id" header="Account ID" />
            <Column field="old_nick" header="Old Nickname" />
            <Column field="new_nick" header="New Nickname" />
            <Column field="approved_by" header="Approved By" />
            <Column field="date" header="Date" />

            <template #empty>
                <div class="text-center py-4 text-muted-color">No nickname changes found</div>
            </template>
        </DataTable>
    </div>
</template>
