<script setup>
import { ref } from 'vue'
import { useLayout } from '@/layout/composables/layout'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import AppConfigurator from './AppConfigurator.vue'
import api from '@/service/api'

const { toggleMenu, toggleDarkMode, isDarkTheme } = useLayout()
const authStore = useAuthStore()
const router = useRouter()

const serverMenu = ref()
const serverItems = ref([
    { label: 'Server One', value: 'one' },
    { label: 'Server Two', value: 'two' },
    { label: 'Server Three', value: 'three' }
])

function toggleServerMenu(event) {
    serverMenu.value.toggle(event)
}

function switchServer(server) {
    if (authStore.isServerUnlocked(server)) {
        authStore.switchServer(server)
        window.location.reload()
    } else {
        router.push({ name: 'unlock', query: { server } })
    }
}

async function goToControlPanel() {
    try {
        const { data } = await api.post('/cp/prepare')
        if (data.token) {
            window.open(`https://monser-dm.nl/cp?t=${encodeURIComponent(data.token)}`, '_blank')
        }
    } catch (err) {
        console.warn('cp access denied:', err.response?.data?.error || err.message)
    }
}
</script>

<template>
    <div class="layout-topbar">
        <div class="layout-topbar-logo-container">
            <button class="layout-menu-button layout-topbar-action" @click="toggleMenu">
                <i class="pi pi-bars"></i>
            </button>
            <router-link to="/" class="layout-topbar-logo">
                <i class="pi pi-shield text-2xl text-primary"></i>
                <span>Admin Panel</span>
            </router-link>
        </div>

        <div class="layout-topbar-actions">
            <div class="flex items-center gap-2">
                <Button 
                    :label="authStore.currentServer ? authStore.currentServer.charAt(0).toUpperCase() + authStore.currentServer.slice(1) : 'Select'" 
                    icon="pi pi-server"
                    text
                    @click="toggleServerMenu"
                />
                <Menu ref="serverMenu" :model="serverItems" popup>
                    <template #item="{ item }">
                        <div 
                            class="flex items-center gap-2 p-2 cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800"
                            @click="switchServer(item.value)"
                        >
                            <i class="pi pi-server"></i>
                            <span>{{ item.label }}</span>
                            <i v-if="item.value === authStore.currentServer" class="pi pi-check text-green-500 ml-auto"></i>
                            <i v-else-if="authStore.isServerUnlocked(item.value)" class="pi pi-unlock text-green-500 ml-auto"></i>
                            <i v-else class="pi pi-lock text-muted-color ml-auto"></i>
                        </div>
                    </template>
                </Menu>
            </div>
            
            <div class="layout-config-menu">
                <button
                    type="button"
                    class="layout-topbar-action"
                    title="Control Panel (Filament)"
                    @click="goToControlPanel"
                >
                    <i class="pi pi-cog"></i>
                </button>
                <button type="button" class="layout-topbar-action" @click="toggleDarkMode">
                    <i :class="['pi', { 'pi-moon': isDarkTheme, 'pi-sun': !isDarkTheme }]"></i>
                </button>
                <div class="relative">
                    <button
                        v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'p-anchored-overlay-enter-active', leaveToClass: 'hidden', leaveActiveClass: 'p-anchored-overlay-leave-active', hideOnOutsideClick: true }"
                        type="button"
                        class="layout-topbar-action layout-topbar-action-highlight"
                    >
                        <i class="pi pi-palette"></i>
                    </button>
                    <AppConfigurator />
                </div>
            </div>

            <button
                class="layout-topbar-menu-button layout-topbar-action"
                v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'p-anchored-overlay-enter-active', leaveToClass: 'hidden', leaveActiveClass: 'p-anchored-overlay-leave-active', hideOnOutsideClick: true }"
            >
                <i class="pi pi-ellipsis-v"></i>
            </button>

            <div class="layout-topbar-menu hidden lg:block">
                <div class="layout-topbar-menu-content">
                    <div v-if="authStore.user" class="flex items-center gap-2 px-4">
                        <span class="text-muted-color">{{ authStore.user.name }}</span>
                        <Tag v-if="authStore.admin" size="small">Lv.{{ authStore.admin.level }}</Tag>
                    </div>
                    <button type="button" class="layout-topbar-action" @click="authStore.logout">
                        <i class="pi pi-sign-out"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
