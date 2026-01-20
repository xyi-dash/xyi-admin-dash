<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/service/api'

const authStore = useAuthStore()

const loading = ref(false)
const data = ref([])
const filters = ref({
    removed: '',
    removed_by: '',
    level: ''
})

onMounted(async () => {
    await loadData()
})

async function loadData() {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.value.removed) params.append('removed', filters.value.removed)
        if (filters.value.removed_by) params.append('removed_by', filters.value.removed_by)
        if (filters.value.level) params.append('level', filters.value.level)
        if (authStore.currentServer) params.append('server', authStore.currentServer)
        
        const response = await api.get(`/admin/logs/removed?${params}`)
        data.value = response.data.data || []
    } catch (error) {
        // see Bans.vue
        console.warn('removed admins are hiding')
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
        <h5>Removed Admins Log</h5>
        
        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.removed" placeholder="Removed admin" class="w-40" />
            <InputText v-model="filters.removed_by" placeholder="Removed by" class="w-40" />
            <InputText v-model="filters.level" placeholder="Level" type="number" class="w-24" />
            <Button label="Search" icon="pi pi-search" @click="search" />
        </div>
        
        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="target" header="Removed" />
            <Column field="admin" header="Removed By" />
            <Column field="level" header="Level" />
            <Column field="reason" header="Reason" />
            <Column field="date" header="Date" />
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">No removed admins found</div>
            </template>
        </DataTable>
    </div>
</template>

