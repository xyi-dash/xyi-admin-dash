<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '@/service/api'

const toast = useToast()

const loading = ref(false)
const saving = ref({})
const servers = ref(null)

const donateMultiplierOptions = [
    { label: 'Off', value: 0 },
    { label: 'x2', value: 1 },
    { label: 'x3', value: 2 }
]

const serverLabels = {
    one: 'Server: 01',
    two: 'Server: 02', 
    three: 'Server: 03'
}

onMounted(async () => {
    await loadData()
})

async function loadData() {
    loading.value = true
    try {
        const response = await api.get('/admin/servers')
        servers.value = response.data.servers
    } catch (error) {
        /*
         * i wrote this. the server were down. my wang was cold.
         * somewhere in the distance a dog was barking. i questioned my life choices.
         * the error persisted. i added this comment. it didnt help.
         */
        console.warn('servers are playing hide and seek')
    } finally {
        loading.value = false
    }
}

async function saveServer(serverName) {
    const settings = servers.value[serverName]
    if (!settings) return
    
    saving.value[serverName] = true
    try {
        await api.post('/admin/servers', {
            server: serverName,
            donate_multiplier: settings.donate_multiplier,
            discounts_enabled: settings.discounts_enabled,
            ads_enabled: settings.ads_enabled,
            ads_link: settings.ads_link,
            ads_description: settings.ads_description
        })
        
        toast.add({ severity: 'success', summary: 'Saved', detail: `${serverLabels[serverName] || serverName} settings updated`, life: 3000 })
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save settings', life: 3000 })
    } finally {
        saving.value[serverName] = false
    }
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="card">
            <div class="flex items-start gap-3">
                <i class="pi pi-cog text-primary text-2xl mt-1"></i>
                <div>
                    <h2 class="text-xl font-semibold m-0">Server Settings</h2>
                    <p class="text-muted-color m-0 mt-1">Configure donate multipliers, discounts and ads for each server</p>
                </div>
            </div>
        </div>
        
        <ProgressSpinner v-if="loading" class="flex justify-center py-8" />
        <div v-else-if="servers" class="flex flex-col lg:flex-row lg:items-start gap-4">
            <div v-for="(settings, serverName) in servers" :key="serverName" class="card flex-1">
                <div class="flex items-center gap-2 mb-4">
                    <i class="pi pi-server text-primary"></i>
                    <h6 class="m-0 font-semibold text-lg">{{ serverLabels[serverName] || serverName }}</h6>
                </div>
                
                <div v-if="settings" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-muted-color">Donate Multiplier</label>
                        <Select 
                            v-model="settings.donate_multiplier" 
                            :options="donateMultiplierOptions"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full"
                        />
                    </div>
                    <div class="flex flex-col gap-3 p-3 bg-surface-100 dark:bg-surface-800 rounded-lg">
                        <div class="flex items-center justify-between">
                            <label class="cursor-pointer">Discounts</label>
                            <InputSwitch v-model="settings.discounts_enabled" />
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="cursor-pointer">Ads</label>
                            <InputSwitch v-model="settings.ads_enabled" />
                        </div>
                    </div>
                    <template v-if="settings.ads_enabled">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm text-muted-color">Ads Link</label>
                            <InputText v-model="settings.ads_link" placeholder="https://..." class="w-full" />
                        </div>
                        
                        <div class="flex flex-col gap-2">
                            <label class="text-sm text-muted-color">Ads Description</label>
                            <InputText v-model="settings.ads_description" placeholder="Short description..." class="w-full" />
                        </div>
                    </template>
                    
                    <Button 
                        label="Save Changes" 
                        icon="pi pi-check"
                        :loading="saving[serverName]"
                        class="mt-2"
                        @click="saveServer(serverName)"
                    />
                </div>
                
                <div v-else class="text-center py-6">
                    <i class="pi pi-exclamation-circle text-2xl text-muted-color mb-2"></i>
                    <p class="text-muted-color m-0">Failed to load settings</p>
                </div>
            </div>
        </div>
        
        <div v-else class="card text-center py-8">
            <i class="pi pi-exclamation-triangle text-5xl text-yellow-500 mb-4"></i>
            <h3 class="text-xl font-semibold mb-2">Failed to load data</h3>
            <p class="text-muted-color">Could not retrieve server settings</p>
        </div>
    </div>
</template>
