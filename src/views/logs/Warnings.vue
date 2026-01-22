<script setup>
import api from '@/service/api'
import { useAuthStore } from '@/stores/auth'
import { onMounted, ref } from 'vue'

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
        <h5>{{ $t('logs.warnings.title') }}</h5>
        
        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.issued_by" :placeholder="$t('logs.warnings.issued_by_placeholder')" class="w-40" />
            <InputText v-model="filters.issued_to" :placeholder="$t('logs.warnings.issued_to_placeholder')" class="w-40" />
            <InputText v-model="filters.reason" :placeholder="$t('logs.warnings.reason_placeholder')" class="w-40" />
            <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
        </div>
        
        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="admin" :header="$t('logs.warnings.issued_by')" />
            <Column field="target" :header="$t('logs.warnings.issued_to')" />
            <Column field="reason" :header="$t('logs.warnings.reason')" />
            <Column field="date" :header="$t('logs.warnings.date')" />
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">{{ $t('logs.warnings.no_warnings') }}</div>
            </template>
        </DataTable>
    </div>
</template>

