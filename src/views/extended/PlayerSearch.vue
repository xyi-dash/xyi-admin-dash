<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/service/api'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(false)
const searched = ref(false)
const data = ref([])
const filters = ref({
    nickname: '',
    account_id: ''
})

async function search() {
    if (!filters.value.nickname && !filters.value.account_id) return
    
    loading.value = true
    searched.value = false
    try {
        const params = new URLSearchParams()
        if (filters.value.nickname) params.append('nickname', filters.value.nickname)
        if (filters.value.account_id) params.append('account_id', filters.value.account_id)
        if (authStore.currentServer) params.append('server', authStore.currentServer)
        
        const response = await api.get(`/admin/players/search?${params}`)
        data.value = response.data.data || []
        searched.value = true
    } catch (error) {
        console.warn('player search hit a wall')
    } finally {
        loading.value = false
    }
}

function viewPlayer(id) {
    router.push({ name: 'extended-player-stats', params: { id } })
}
</script>

<template>
    <div class="card">
        <h5>Player Search</h5>
        
        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.nickname" placeholder="Nickname" class="w-48" @keyup.enter="search" />
            <InputText v-model="filters.account_id" placeholder="Account ID" type="number" class="w-32" @keyup.enter="search" />
            <Button label="Search" icon="pi pi-search" @click="search" />
        </div>
        
        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column header="ID">
                <template #body="{ data }">
                    <Button :label="String(data.id)" link class="p-0" @click="viewPlayer(data.id)" />
                </template>
            </Column>
            <Column field="name" header="Name" />
            <Column field="level" header="Level" />
            <Column field="kills" header="Kills" />
            <Column field="deaths" header="Deaths" />
            <Column field="donate_money" header="Donate" />
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">
                    {{ searched ? 'No players found' : 'Enter nickname or account ID to search' }}
                </div>
            </template>
        </DataTable>
    </div>
</template>

