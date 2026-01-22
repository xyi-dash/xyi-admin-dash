<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'primevue/usetoast'
import api from '@/service/api'

const { t } = useI18n()
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const loading = ref(true)
const adminList = ref(null)
const expandedLevels = ref({})

const addDialog = ref(false)
const addLoading = ref(false)
const newAdmin = ref({
    nickname: '',
    level: 1,
    reason: ''
})

const canAddAdmin = computed(() => {
    if (!authStore.admin) return false
    const level = authStore.admin.level || 0
    const isGA = authStore.admin.is_ga || false
    return level >= 7 || (level === 6 && isGA)
})

const maxAssignableLevel = computed(() => {
    if (!authStore.admin) return 5
    return authStore.admin.level >= 7 ? 6 : 5
})

const levelOptions = computed(() => {
    const max = maxAssignableLevel.value
    return Array.from({ length: max }, (_, i) => ({
        label: `${t('common.level')} ${i + 1}`,
        value: i + 1
    }))
})

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
        // it happens
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

const canViewDetails = computed(() => adminList.value?.can_view_details ?? false)

function openAdmin(admin) {
    if (!canViewDetails.value) return
    router.push({ name: 'admin-manage', params: { name: admin.name } })
}

function toggleLevel(level) {
    expandedLevels.value[level] = !expandedLevels.value[level]
}

function openAddDialog() {
    newAdmin.value = { nickname: '', level: 1, reason: '' }
    addDialog.value = true
}

async function submitNewAdmin() {
    if (!newAdmin.value.nickname.trim()) {
        toast.add({ severity: 'warn', summary: t('common.warning'), detail: t('admins.add_dialog.nickname_required'), life: 3000 })
        return
    }
    if (!newAdmin.value.reason.trim()) {
        toast.add({ severity: 'warn', summary: t('common.warning'), detail: t('admins.add_dialog.reason_required'), life: 3000 })
        return
    }

    addLoading.value = true
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : ''
        await api.post(`/admin/manage/add${serverParam}`, {
            nickname: newAdmin.value.nickname,
            level: newAdmin.value.level,
            reason: newAdmin.value.reason
        })
        
        toast.add({ severity: 'success', summary: t('common.success'), detail: t('admins.add_dialog.success'), life: 3000 })
        addDialog.value = false
        await loadAdmins()
    } catch (error) {
        const msg = error.response?.data?.error || t('admins.add_dialog.failed')
        toast.add({ severity: 'error', summary: t('common.error'), detail: msg, life: 5000 })
    } finally {
        addLoading.value = false
    }
}
</script>

<template>
    <div class="card">
        <div class="flex justify-between items-center mb-4">
            <h5 class="m-0">{{ $t('admins.title') }}</h5>
            <div class="flex gap-4 items-center">
                <Button 
                    v-if="canAddAdmin"
                    :label="$t('admins.add_admin')"
                    icon="pi pi-plus"
                    size="small"
                    @click="openAddDialog"
                />
                <template v-if="adminList">
                    <Tag severity="info">{{ $t('common.total') }}: {{ adminList.total }}</Tag>
                    <Tag severity="success">{{ $t('common.online') }}: {{ adminList.online }}</Tag>
                </template>
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
                    <span class="font-semibold">{{ $t('common.level') }} {{ level }}</span>
                    <Tag size="small">{{ getAdminsByLevel(level).length }}</Tag>
                </div>
                
                <DataTable 
                    v-if="expandedLevels[level]"
                    :value="getAdminsByLevel(level)"
                    stripedRows
                    class="p-datatable-sm"
                >
                    <Column field="name" :header="$t('common.name')">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <Button 
                                    v-if="canViewDetails"
                                    :label="data.name" 
                                    link 
                                    class="p-0"
                                    @click="openAdmin(data)"
                                />
                                <span v-else>{{ data.name }}</span>
                                <Tag v-if="data.is_support" severity="info" size="small">SUP</Tag>
                                <Tag v-if="data.is_youtuber" severity="warn" size="small">YT</Tag>
                            </div>
                        </template>
                    </Column>
                    <Column :header="$t('admins.ga')">
                        <template #body="{ data }">
                            <Tag v-if="data.is_ga" severity="success" size="small">GA</Tag>
                            <span v-else class="text-muted-color">-</span>
                        </template>
                    </Column>
                    <Column field="warnings" :header="$t('admins.warns')">
                        <template #body="{ data }">
                            <span :class="{ 'text-red-500 font-bold': data.warnings >= 2 }">
                                {{ data.warnings }}/3
                            </span>
                        </template>
                    </Column>
                    <Column :header="$t('admins.rep')">
                        <template #body="{ data }">
                            <span class="text-green-500">+{{ data.reputation?.up || 0 }}</span>
                            /
                            <span class="text-red-500">-{{ data.reputation?.down || 0 }}</span>
                        </template>
                    </Column>
                    <Column field="playtime_3days" :header="$t('admins.three_days')" />
                    <Column field="playtime_week" :header="$t('admins.week')" />
                    <Column :header="$t('common.status')">
                        <template #body="{ data }">
                            <Tag :severity="data.is_online ? 'success' : 'secondary'" size="small">
                                {{ data.is_online ? $t('common.online') : $t('common.offline') }}
                            </Tag>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
        
        <div v-else class="text-center py-8">
            <i class="pi pi-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
            <p class="text-muted-color">{{ $t('admins.failed_load') }}</p>
        </div>
    </div>

    <Dialog v-model:visible="addDialog" :header="$t('admins.add_dialog.title')" modal :style="{ width: '400px' }">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-2">
                <label for="nickname">{{ $t('admins.add_dialog.nickname') }}</label>
                <InputText id="nickname" v-model="newAdmin.nickname" :placeholder="$t('admins.add_dialog.nickname_placeholder')" />
            </div>
            <div class="flex flex-col gap-2">
                <label for="level">{{ $t('admins.add_dialog.level') }}</label>
                <Select 
                    id="level"
                    v-model="newAdmin.level" 
                    :options="levelOptions"
                    optionLabel="label"
                    optionValue="value"
                    :placeholder="$t('admins.add_dialog.select_level')"
                />
            </div>
            <div class="flex flex-col gap-2">
                <label for="reason">{{ $t('admins.add_dialog.reason') }}</label>
                <Textarea id="reason" v-model="newAdmin.reason" rows="3" :placeholder="$t('admins.add_dialog.reason_placeholder')" />
            </div>
        </div>
        <template #footer>
            <Button :label="$t('common.cancel')" text @click="addDialog = false" />
            <Button :label="$t('admins.add_admin')" :loading="addLoading" @click="submitNewAdmin" />
        </template>
    </Dialog>
</template>

