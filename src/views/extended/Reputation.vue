<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/service/api'

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
        <h5>Reputation Logs</h5>
        
        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.from" placeholder="From player" class="w-40" />
            <InputText v-model="filters.to" placeholder="To player" class="w-40" />
            <Button label="Search" icon="pi pi-search" @click="search" />
        </div>
        
        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column header="From">
                <template #body="{ data }">
                    {{ data.from }}
                    <Tag v-if="data.from_is_banned" severity="danger" size="small" class="ml-1">BAN</Tag>
                </template>
            </Column>
            <Column header="To">
                <template #body="{ data }">
                    {{ data.to }}
                    <Tag v-if="data.to_is_banned" severity="danger" size="small" class="ml-1">BAN</Tag>
                </template>
            </Column>
            <Column field="type" header="Type">
                <template #body="{ data }">
                    <Tag :severity="data.type === '+' ? 'success' : 'danger'">{{ data.type }}</Tag>
                </template>
            </Column>
            <Column field="comment" header="Comment" />
            <Column field="date" header="Date" />
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">No reputation logs found</div>
            </template>
        </DataTable>
    </div>
</template>

