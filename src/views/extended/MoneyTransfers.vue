<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/service/api'

const authStore = useAuthStore()

const loading = ref(false)
const data = ref([])
const filters = ref({
    from_name: '',
    to_name: ''
})

onMounted(async () => {
    await loadData()
})

async function loadData() {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.value.from_name) params.append('from_name', filters.value.from_name)
        if (filters.value.to_name) params.append('to_name', filters.value.to_name)
        if (authStore.currentServer) params.append('server', authStore.currentServer)
        
        const response = await api.get(`/admin/extended/money-transfers?${params}`)
        data.value = response.data.data || []
    } catch (error) {
        console.warn('money evaporated')
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
        <h5>Money Transfer Logs</h5>
        
        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.from_name" placeholder="From player" class="w-40" />
            <InputText v-model="filters.to_name" placeholder="To player" class="w-40" />
            <Button label="Search" icon="pi pi-search" @click="search" />
        </div>
        
        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column header="From">
                <template #body="{ data }">
                    {{ data.from_name }}
                    <Tag v-if="data.from_is_banned" severity="danger" size="small" class="ml-1">BAN</Tag>
                </template>
            </Column>
            <Column header="To">
                <template #body="{ data }">
                    {{ data.to_name }}
                    <Tag v-if="data.to_is_banned" severity="danger" size="small" class="ml-1">BAN</Tag>
                </template>
            </Column>
            <Column header="Amount">
                <template #body="{ data }">
                    <span class="text-green-500 font-semibold">${{ data.amount?.toLocaleString() }}</span>
                </template>
            </Column>
            <Column field="date" header="Date" />
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">No money transfers found</div>
            </template>
        </DataTable>
    </div>
</template>

