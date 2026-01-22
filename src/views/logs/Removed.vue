<script setup>
import api from '@/service/api'
import { useAuthStore } from '@/stores/auth'
import { onMounted, ref } from 'vue'

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
        <h5>{{ $t('logs.removed.title') }}</h5>
        
        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.removed" :placeholder="$t('logs.removed.removed_placeholder')" class="w-40" />
            <InputText v-model="filters.removed_by" :placeholder="$t('logs.removed.removed_by_placeholder')" class="w-40" />
            <InputText v-model="filters.level" :placeholder="$t('logs.removed.level_placeholder')" type="number" class="w-24" />
            <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
        </div>
        
        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="target" :header="$t('logs.removed.removed')" />
            <Column field="admin" :header="$t('logs.removed.removed_by')" />
            <Column field="level" :header="$t('logs.removed.level')" />
            <Column field="reason" :header="$t('logs.removed.reason')" />
            <Column field="date" :header="$t('logs.removed.date')" />
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">{{ $t('logs.removed.no_removed') }}</div>
            </template>
        </DataTable>
    </div>
</template>

