<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'primevue/usetoast'
import api from '@/service/api'

const authStore = useAuthStore()
const toast = useToast()

const loading = ref(false)
const data = ref([])
const filters = ref({
    player: '',
    admin: ''
})

const editDialog = ref(false)
const saving = ref(false)
const editingBan = ref(null)
const editReason = ref('')

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
    } finally {
        loading.value = false
    }
}

function search() {
    loadData()
}

function openEditDialog(ban) {
    editingBan.value = ban
    editReason.value = ban.reason || ''
    editDialog.value = true
}

async function saveReason() {
    if (!editingBan.value) return
    
    saving.value = true
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : ''
        await api.patch(`/admin/extended/bans/${editingBan.value.id}/reason${serverParam}`, {
            reason: editReason.value
        })
        
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Ban reason updated',
            life: 3000
        })
        
        editDialog.value = false
        await loadData()
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Failed to update reason',
            life: 5000
        })
    } finally {
        saving.value = false
    }
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
            <Column field="reason" header="Reason">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <span class="flex-1">{{ data.reason }}</span>
                        <Button 
                            icon="pi pi-pencil" 
                            text 
                            rounded 
                            size="small"
                            @click="openEditDialog(data)"
                            v-tooltip.top="'Edit reason'"
                        />
                    </div>
                </template>
            </Column>
            <Column field="date" header="Date" />
            
            <template #empty>
                <div class="text-center py-4 text-muted-color">No permanent bans found</div>
            </template>
        </DataTable>
        
        <Dialog 
            v-model:visible="editDialog" 
            header="Edit Ban Reason" 
            modal 
            class="w-full max-w-lg"
        >
            <div class="flex flex-col gap-4">
                <div v-if="editingBan" class="text-muted-color text-sm">
                    Player: <span class="font-semibold text-color">{{ editingBan.name }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="font-semibold">Reason</label>
                    <Textarea 
                        v-model="editReason" 
                        rows="3" 
                        class="w-full"
                        placeholder="reason for ban"
                    />
                </div>
            </div>
            <template #footer>
                <Button label="Cancel" text @click="editDialog = false" />
                <Button label="Save" :loading="saving" @click="saveReason" />
            </template>
        </Dialog>
    </div>
</template>

