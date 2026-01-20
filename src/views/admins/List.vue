<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/service/api'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const adminList = ref(null)
const expandedLevels = ref({})

onMounted(async () => {
    await loadAdmins()
})

async function loadAdmins() {
    loading.value = true
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : ''
        const { data } = await api.get(`/admin/list${serverParam}`)
        adminList.value = data
        const levels = [...new Set(data.admins.map(a => a.level))].sort((a, b) => b - a)
        levels.forEach(level => {
            expandedLevels.value[level] = true
        })
    } catch (error) {
        console.warn('admin list went somewhere. probably.')
    } finally {
        loading.value = false
    }
}

const availableLevels = computed(() => {
    if (!adminList.value?.admins) return []
    return [...new Set(adminList.value.admins.map(a => a.level))].sort((a, b) => b - a)
})

function getAdminsByLevel(level) {
    if (!adminList.value?.admins) return []
    return adminList.value.admins.filter(a => a.level === level)
}

function openAdmin(admin) {
    router.push({ name: 'admin-manage', params: { name: admin.name } })
}

function toggleLevel(level) {
    expandedLevels.value[level] = !expandedLevels.value[level]
}
</script>

<template>
    <div class="card">
        <div class="flex justify-between items-center mb-4">
            <h5 class="m-0">Admin List</h5>
            <div v-if="adminList" class="flex gap-4">
                <Tag severity="info">Total: {{ adminList.total }}</Tag>
                <Tag severity="success">Online: {{ adminList.online }}</Tag>
            </div>
        </div>
        
        <ProgressSpinner v-if="loading" class="flex justify-center" />
        
        <div v-else-if="adminList">
            <div v-for="level in availableLevels" :key="level" class="mb-4">
                <div 
                    class="flex items-center gap-2 cursor-pointer p-3 bg-surface-100 dark:bg-surface-800 rounded-lg mb-2"
                    @click="toggleLevel(level)"
                >
                    <i :class="['pi', expandedLevels[level] ? 'pi-chevron-down' : 'pi-chevron-right']"></i>
                    <span class="font-semibold">Level {{ level }}</span>
                    <Tag size="small">{{ getAdminsByLevel(level).length }}</Tag>
                </div>
                
                <DataTable 
                    v-if="expandedLevels[level]"
                    :value="getAdminsByLevel(level)"
                    stripedRows
                    class="p-datatable-sm"
                >
                    <Column field="name" header="Name">
                        <template #body="{ data }">
                            <Button 
                                :label="data.name" 
                                link 
                                class="p-0"
                                @click="openAdmin(data)"
                            />
                        </template>
                    </Column>
                    <Column header="GA">
                        <template #body="{ data }">
                            <Tag v-if="data.is_ga" severity="success" size="small">GA</Tag>
                            <span v-else class="text-muted-color">-</span>
                        </template>
                    </Column>
                    <Column field="warnings" header="Warns">
                        <template #body="{ data }">
                            <span :class="{ 'text-red-500 font-bold': data.warnings >= 2 }">
                                {{ data.warnings }}/3
                            </span>
                        </template>
                    </Column>
                    <Column header="Reputation">
                        <template #body="{ data }">
                            <span class="text-green-500">+{{ data.reputation?.up || 0 }}</span>
                            /
                            <span class="text-red-500">-{{ data.reputation?.down || 0 }}</span>
                        </template>
                    </Column>
                    <Column field="playtime_3days" header="3 Days" />
                    <Column field="playtime_week" header="Week" />
                    <Column header="Status">
                        <template #body="{ data }">
                            <Tag :severity="data.is_online ? 'success' : 'secondary'" size="small">
                                {{ data.is_online ? 'Online' : 'Offline' }}
                            </Tag>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
        
        <div v-else class="text-center py-8">
            <i class="pi pi-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
            <p class="text-muted-color">Failed to load admin list</p>
        </div>
    </div>
</template>

