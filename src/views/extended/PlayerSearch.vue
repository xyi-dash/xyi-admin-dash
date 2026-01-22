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
        <h5>{{ $t('extended.player_search.title') }}</h5>
        
        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.nickname" :placeholder="$t('extended.player_search.nickname')" class="w-48" @keyup.enter="search" />
            <InputText v-model="filters.account_id" :placeholder="$t('extended.player_search.account_id')" type="number" class="w-32" @keyup.enter="search" />
            <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
        </div>
        
        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column :header="$t('extended.player_search.id')">
                <template #body="{ data }">
                    <Button :label="String(data.id)" link class="p-0" @click="viewPlayer(data.id)" />
                </template>
            </Column>
            <Column field="name" :header="$t('extended.player_search.name')" />
            <Column field="level" :header="$t('extended.player_search.level')" />
            <Column field="kills" :header="$t('extended.player_search.kills')" />
            <Column field="deaths" :header="$t('extended.player_search.deaths')" />
            <Column field="donate_money" :header="$t('extended.player_search.donate')" />
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">
                    {{ searched ? $t('extended.player_search.no_players') : $t('extended.player_search.enter_search') }}
                </div>
            </template>
        </DataTable>
    </div>
</template>

