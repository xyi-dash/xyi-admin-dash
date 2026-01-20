<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/service/api'

const authStore = useAuthStore()

const loading = ref(false)
const data = ref([])
const page = ref(0)
const filters = ref({
    admin: '',
    player: '',
    cmd: ''
})

onMounted(async () => {
    await loadData()
})

async function loadData() {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.value.admin) params.append('admin', filters.value.admin)
        if (filters.value.player) params.append('player', filters.value.player)
        if (filters.value.cmd) params.append('cmd', filters.value.cmd)
        params.append('page', page.value)
        if (authStore.currentServer) params.append('server', authStore.currentServer)
        
        const response = await api.get(`/admin/logs/actions?${params}`)
        data.value = response.data.data || []
    } catch (error) {
        console.warn('actions log said no')
    } finally {
        loading.value = false
    }
}

function search() {
    page.value = 0
    loadData()
}

function prevPage() {
    if (page.value > 0) {
        page.value--
        loadData()
    }
}

function nextPage() {
    page.value++
    loadData()
}
</script>

<template>
    <div class="card">
        <h5>Admin Actions Log</h5>
        
        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.admin" placeholder="Admin name" class="w-40" />
            <InputText v-model="filters.player" placeholder="Player name" class="w-40" />
            <InputText v-model="filters.cmd" placeholder="Command" class="w-40" />
            <Button label="Search" icon="pi pi-search" @click="search" />
        </div>
        
        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="admin" header="Admin" />
            <Column field="player" header="Player" />
            <Column field="cmd" header="Command" />
            <Column field="amount" header="Amount" />
            <Column field="reason" header="Reason" />
            <Column field="date" header="Date" />
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">No actions found</div>
            </template>
        </DataTable>
        
        <div class="flex justify-between items-center mt-4">
            <span class="text-muted-color">Page {{ page + 1 }}</span>
            <div class="flex gap-2">
                <Button icon="pi pi-chevron-left" text :disabled="page === 0" @click="prevPage" />
                <Button icon="pi pi-chevron-right" text @click="nextPage" />
            </div>
        </div>
    </div>
</template>

