<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/service/api'

const authStore = useAuthStore()

const loading = ref(false)
const data = ref([])
const filters = ref({
    player: '',
    admin: ''
})

onMounted(async () => {
    await loadData()
})

async function loadData() {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.value.player) params.append('player', filters.value.player)
        if (filters.value.admin) params.append('admin', filters.value.admin)
        if (authStore.currentServer) params.append('server', authStore.currentServer)
        
        const response = await api.get(`/admin/extended/bans?${params}`)
        data.value = response.data.data || []
    } catch (error) {
        console.warn('bans escaped')
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
        <h5>Permanent Bans</h5>
        
        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.player" placeholder="Player name" class="w-40" />
            <InputText v-model="filters.admin" placeholder="Admin name" class="w-40" />
            <Button label="Search" icon="pi pi-search" @click="search" />
        </div>
        
        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="admin" header="Admin" />
            <Column field="admin_ip" header="Admin IP" />
            <Column field="name" header="Player" />
            <Column field="player_ip" header="Player IP" />
            <Column field="reason" header="Reason" />
            <Column field="date" header="Date" />
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">No permanent bans found</div>
            </template>
        </DataTable>
    </div>
</template>

