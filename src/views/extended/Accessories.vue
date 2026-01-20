<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/service/api'

const authStore = useAuthStore()

const loading = ref(false)
const data = ref([])
const filters = ref({
    account_name: '',
    accessory: ''
})

onMounted(async () => {
    await loadData()
})

async function loadData() {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.value.account_name) params.append('account_name', filters.value.account_name)
        if (filters.value.accessory) params.append('accessory', filters.value.accessory)
        if (authStore.currentServer) params.append('server', authStore.currentServer)
        
        const response = await api.get(`/admin/extended/accessories?${params}`)
        data.value = response.data.data || []
    } catch (error) {
        // same pattern as MoneyTransfers.vue, ctrl+c ctrl+v engineering at its finest
        console.warn('accessories said no')
    } finally {
        loading.value = false
    }
}

function search() {
    loadData()
}
</script>

<template>
    <div class="card">
        <h5>Accessory Logs</h5>
        
        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.account_name" placeholder="Player name" class="w-40" />
            <InputText v-model="filters.accessory" placeholder="Accessory" class="w-40" />
            <Button label="Search" icon="pi pi-search" @click="search" />
        </div>
        
        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="account_id" header="Account ID" />
            <Column field="account_name" header="Player" />
            <Column field="accessory_name" header="Accessory" />
            <Column field="action" header="Action" />
            <Column field="account_ip" header="IP" />
            <Column field="date" header="Date" />
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">No accessory logs found</div>
            </template>
        </DataTable>
    </div>
</template>

