<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/service/api'

const authStore = useAuthStore()

const loading = ref(false)
const data = ref([])
const page = ref(0)
const filters = ref({
    ga: '',
    target: '',
    type: ''
})

const actionTypes = [
    { label: 'All', value: '' },
    { label: 'Warn', value: '1' },
    { label: 'Unwarn', value: '2' },
    { label: 'Promote', value: '3' },
    { label: 'Demote', value: '4' },
    { label: 'Remove', value: '5' },
    { label: 'Appoint', value: '6' }
]

onMounted(async () => {
    await loadData()
})

async function loadData() {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.value.ga) params.append('ga', filters.value.ga)
        if (filters.value.target) params.append('target', filters.value.target)
        if (filters.value.type) params.append('type', filters.value.type)
        params.append('page', page.value)
        if (authStore.currentServer) params.append('server', authStore.currentServer)
        
        const response = await api.get(`/admin/logs/ga-actions?${params}`)
        data.value = response.data.data || []
    } catch (error) {
        console.warn('ga actions went poof')
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
        <h5>GA Actions Log</h5>
        
        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.ga" placeholder="GA name" class="w-40" />
            <InputText v-model="filters.target" placeholder="Target" class="w-40" />
            <Select v-model="filters.type" :options="actionTypes" optionLabel="label" optionValue="value" placeholder="Action type" class="w-40" />
            <Button label="Search" icon="pi pi-search" @click="search" />
        </div>
        
        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="admin" header="GA" />
            <Column field="target" header="Target" />
            <Column field="type_name" header="Action" />
            <Column field="amount" header="Amount" />
            <Column field="reason" header="Reason" />
            <Column field="date" header="Date" />
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">No GA actions found</div>
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

