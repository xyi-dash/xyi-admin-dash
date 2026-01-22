<script setup>
import api from '@/service/api'
import { useAuthStore } from '@/stores/auth'
import { onMounted, ref } from 'vue'

const authStore = useAuthStore()

const loading = ref(false)
const data = ref([])
const filters = ref({
    from: '',
    to: ''
})

onMounted(async () => {
    await loadData()
})

async function loadData() {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.value.from) params.append('from', filters.value.from)
        if (filters.value.to) params.append('to', filters.value.to)
        if (authStore.currentServer) params.append('server', authStore.currentServer)
        
        const response = await api.get(`/admin/extended/reputation?${params}`)
        data.value = response.data.data || []
    } catch (error) {
        // reputation. what even is reputation. internet points? meaningless validation from strangers?
        // ...anyway the api broke
        console.warn('reputation logs said nope')
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
        <h5>{{ $t('extended.reputation.title') }}</h5>
        
        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.from" :placeholder="$t('extended.reputation.from_placeholder')" class="w-40" />
            <InputText v-model="filters.to" :placeholder="$t('extended.reputation.to_placeholder')" class="w-40" />
            <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
        </div>
        
        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column :header="$t('extended.reputation.from')">
                <template #body="{ data }">
                    {{ data.from }}
                    <Tag v-if="data.from_is_banned" severity="danger" size="small" class="ml-1">BAN</Tag>
                </template>
            </Column>
            <Column :header="$t('extended.reputation.to')">
                <template #body="{ data }">
                    {{ data.to }}
                    <Tag v-if="data.to_is_banned" severity="danger" size="small" class="ml-1">BAN</Tag>
                </template>
            </Column>
            <Column field="type" :header="$t('extended.reputation.type')">
                <template #body="{ data }">
                    <Tag :severity="data.type === '+' ? 'success' : 'danger'">{{ data.type }}</Tag>
                </template>
            </Column>
            <Column field="comment" :header="$t('extended.reputation.comment')" />
            <Column field="date" :header="$t('extended.reputation.date')" />
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">{{ $t('extended.reputation.no_logs') }}</div>
            </template>
        </DataTable>
    </div>
</template>

