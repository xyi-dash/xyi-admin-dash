<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'primevue/usetoast'
import api from '@/service/api'

const authStore = useAuthStore()
const toast = useToast()

const loading = ref(false)
const data = ref([])
const page = ref(0)
const filters = ref({
    admin: '',
    vk: '',
    type: ''
})

const purchaseTypes = [
    { label: 'All Types', value: '' },
    { label: 'Buy Admin', value: '1' },
    { label: 'Promotion', value: '2' },
    { label: 'Remove Warning', value: '3' }
]

onMounted(async () => {
    await loadData()
})

async function loadData() {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.value.admin) params.append('admin', filters.value.admin)
        if (filters.value.vk) params.append('vk', filters.value.vk)
        if (filters.value.type) params.append('type', filters.value.type)
        params.append('page', page.value)
        if (authStore.currentServer) params.append('server', authStore.currentServer)
        
        const response = await api.get(`/admin/logs/purchases?${params}`)
        data.value = response.data.data || []
    } catch (error) {
        /*
         * this error handler has seen some shit
         * at 12am. with no stack trace. while the server was on fire.
         * nobody reads these. hello future me. you bastard.
         * anyway here's wang-derwall
         */
        console.warn('purchases log is on vacation. can\'t blame it honestly.')
    } finally {
        loading.value = false
    }
}

async function confirmPurchase(adminName) {
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : ''
        await api.post(`/admin/logs/purchases/confirm${serverParam}`, { admin_name: adminName })
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Purchase confirmed',
            life: 3000
        })
        await loadData()
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to confirm purchase',
            life: 3000
        })
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
        <h5>Purchases Log</h5>
        
        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.admin" placeholder="Admin name" class="w-40" />
            <InputText v-model="filters.vk" placeholder="VK" class="w-40" />
            <Select v-model="filters.type" :options="purchaseTypes" optionLabel="label" optionValue="value" placeholder="Type" class="w-40" />
            <Button label="Search" icon="pi pi-search" @click="search" />
        </div>
        
        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="name" header="Admin" />
            <Column header="VK">
                <template #body="{ data }">
                    <a :href="data.vk_page" target="_blank" class="text-primary">{{ data.vk_page }}</a>
                </template>
            </Column>
            <Column field="type_name" header="Type" />
            <Column field="level" header="Level" />
            <Column field="date" header="Date" />
            <Column header="Action">
                <template #body="{ data }">
                    <Button 
                        v-if="data.needs_confirm" 
                        label="Confirm" 
                        size="small"
                        @click="confirmPurchase(data.name)"
                    />
                    <span v-else class="text-muted-color">-</span>
                </template>
            </Column>
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">No purchases found</div>
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

