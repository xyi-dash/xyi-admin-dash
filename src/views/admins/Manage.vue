<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'primevue/usetoast'
import api from '@/service/api'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const loading = ref(true)
const executing = ref(false)
const admin = ref(null)
const actions = ref([])
const selectedAction = ref('')
const reason = ref('')

const actionLabels = {
    warn: 'Give Warning',
    unwarn: 'Remove Warning',
    promote: 'Promote',
    demote: 'Demote',
    remove: 'Remove Admin',
    give_ga: 'Give GA',
    remove_ga: 'Remove GA',
    confirm: 'Confirm Admin',
    reset_password: 'Reset Password'
}

// these actions need reasons. unlike my decisions to work in frontend. those need therapy.
const actionsNeedingReason = ['warn', 'unwarn', 'promote', 'demote', 'remove', 'give_ga', 'remove_ga']

onMounted(async () => {
    await loadAdminData()
})

async function loadAdminData() {
    loading.value = true
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : ''
        const { data } = await api.get(`/admin/manage/${encodeURIComponent(route.params.name)}/actions${serverParam}`)
        admin.value = data.admin
        actions.value = data.actions
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to load admin data',
            life: 3000
        })
    } finally {
        loading.value = false
    }
}

async function executeAction() {
    if (!selectedAction.value) return
    
    const needsReason = actionsNeedingReason.includes(selectedAction.value)
    if (needsReason && !reason.value.trim()) {
        toast.add({
            severity: 'warn',
            summary: 'Warning',
            detail: 'Please provide a reason',
            life: 3000
        })
        return
    }
    
    executing.value = true
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : ''
        const { data } = await api.post(`/admin/manage${serverParam}`, {
            target_name: admin.value.name,
            action: selectedAction.value,
            reason: reason.value || ' '
        })
        
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Action executed successfully',
            life: 3000
        })
        
        admin.value = data.admin
        selectedAction.value = ''
        reason.value = ''
        await loadAdminData()
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Failed to execute action',
            life: 5000
        })
    } finally {
        executing.value = false
    }
}

function goBack() {
    router.push({ name: 'admins' })
}
</script>

<template>
    <div class="card">
        <div class="flex items-center gap-2 mb-4">
            <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
            <h5 class="m-0">Manage Admin: {{ route.params.name }}</h5>
        </div>
        
        <ProgressSpinner v-if="loading" class="flex justify-center" />
        
        <div v-else-if="admin" class="flex justify-center">
            <div class="card w-full max-w-2xl">
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-xl font-semibold">{{ admin.name }}</span>
                    <span 
                        class="w-2 h-2 rounded-full" 
                        :class="admin.is_online ? 'bg-green-500' : 'bg-red-400'"
                        :title="admin.is_online ? 'Online' : 'Offline'"
                    ></span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <div class="text-muted-color text-sm">Level</div>
                        <div class="font-semibold">{{ admin.level }}{{ admin.is_ga ? '+' : '' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm">Warnings</div>
                        <div class="font-semibold" :class="{ 'text-red-500': admin.warnings >= 2 }">{{ admin.warnings }}/3</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm">Appointed</div>
                        <div class="font-semibold">{{ admin.appointed_date || '-' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm">Appointed By</div>
                        <div class="font-semibold">{{ admin.appointed_by || 'unknown' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm">Last Visit</div>
                        <div class="font-semibold">{{ admin.last_online || '-' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm">Confirmed</div>
                        <Tag :severity="admin.needs_confirm ? 'warn' : 'success'" class="text-xs">
                            {{ admin.needs_confirm ? 'No' : 'Yes' }}
                        </Tag>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm">Reg IP</div>
                        <div class="font-semibold font-mono text-sm">{{ admin.ip_reg || '-' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm">Last IP</div>
                        <div class="font-semibold font-mono text-sm">{{ admin.ip_last || '-' }}</div>
                    </div>
                </div>

                <Divider />

                <h6 class="mb-3">Online Time</h6>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <div class="text-muted-color text-sm">Today</div>
                        <div class="font-semibold font-mono">{{ admin.playtime?.today || '00:00' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm">Yesterday</div>
                        <div class="font-semibold font-mono">{{ admin.playtime?.yesterday || '00:00' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm">Day Before</div>
                        <div class="font-semibold font-mono">{{ admin.playtime?.day_before || '00:00' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-color text-sm">This Week</div>
                        <div class="font-semibold font-mono">{{ admin.playtime?.week || '00:00' }}</div>
                    </div>
                </div>

                <template v-if="admin.stats">
                    <Divider />
                    <h6 class="mb-3">Admin Stats</h6>
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <div class="text-muted-color text-sm">Hours Played</div>
                            <div class="font-semibold">
                                {{ admin.stats.hours_played }}/{{ admin.stats.hours_required }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted-color text-sm">Punishments</div>
                            <div class="font-semibold">
                                {{ admin.stats.punishments }}/{{ admin.stats.punishments_required }}
                            </div>
                        </div>
                        <div>
                            <div class="text-muted-color text-sm">Reports</div>
                            <div class="font-semibold">
                                {{ admin.stats.reports }}/{{ admin.stats.reports_required }}
                            </div>
                        </div>
                    </div>
                </template>

                <Divider />

                <h6 class="mb-3">Actions</h6>
                <div v-if="actions.length" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-2">
                        <Select 
                            v-model="selectedAction" 
                            :options="actions"
                            placeholder="Select an action"
                            class="w-full"
                        >
                            <template #value="{ value }">
                                {{ value ? actionLabels[value] || value : 'Select an action' }}
                            </template>
                            <template #option="{ option }">
                                {{ actionLabels[option] || option }}
                            </template>
                        </Select>
                    </div>
                    
                    <div v-if="selectedAction && actionsNeedingReason.includes(selectedAction)" class="flex flex-col gap-2">
                        <InputText 
                            v-model="reason" 
                            placeholder="Reason"
                            class="w-full"
                        />
                    </div>
                    
                    <Button 
                        label="Execute" 
                        :loading="executing"
                        :disabled="!selectedAction"
                        @click="executeAction"
                    />
                </div>
                
                <div v-else class="text-center py-4">
                    <i class="pi pi-lock text-2xl text-muted-color mb-2"></i>
                    <p class="text-muted-color">No actions available</p>
                </div>
            </div>
        </div>
        
        <div v-else class="text-center py-8">
            <i class="pi pi-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
            <p class="text-muted-color">Admin not found</p>
        </div>
    </div>
</template>

