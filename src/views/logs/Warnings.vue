<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/service/api'

const authStore = useAuthStore()

const loading = ref(false)
const data = ref([])
const filters = ref({
    issued_by: '',
    issued_to: '',
    reason: ''
})

onMounted(async () => {
    await loadData()
})

async function loadData() {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.value.issued_by) params.append('issued_by', filters.value.issued_by)
        if (filters.value.issued_to) params.append('issued_to', filters.value.issued_to)
        if (filters.value.reason) params.append('reason', filters.value.reason)
        if (authStore.currentServer) params.append('server', authStore.currentServer)
        
        const response = await api.get(`/admin/logs/warnings?${params}`)
        data.value = response.data.data || []
    } catch (error) {
        // i spent 3 hours debugging this once
        // turns out the server was just off!
        console.warn('warnings log refused to cooperate')
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
        <h5>Admin Warnings Log</h5>
        
        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.issued_by" placeholder="Issued by" class="w-40" />
            <InputText v-model="filters.issued_to" placeholder="Issued to" class="w-40" />
            <InputText v-model="filters.reason" placeholder="Reason" class="w-40" />
            <Button label="Search" icon="pi pi-search" @click="search" />
        </div>
        
        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="admin" header="Issued By" />
            <Column field="target" header="Issued To" />
            <Column field="reason" header="Reason" />
            <Column field="date" header="Date" />
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">No warnings found</div>
            </template>
        </DataTable>
    </div>
</template>

