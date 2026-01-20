<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AppMenuItem from './AppMenuItem.vue'

const authStore = useAuthStore()

// this menu structure is held together by hopes, dreams, and one very long computed property
// if you're reading this at 3am debugging why a menu item isnt showing up - my condolences
const model = computed(() => {
    const items = [{
        label: 'Home',
        items: [
            { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/' },
            { label: 'Admin List', icon: 'pi pi-fw pi-users', to: '/admins' }
        ]
    }]
    
    if (authStore.canViewLogs) {
        items.push({
            label: 'Logs',
            items: [
                { label: 'Actions', icon: 'pi pi-fw pi-list', to: '/logs/actions' },
                { label: 'Warnings', icon: 'pi pi-fw pi-exclamation-triangle', to: '/logs/warnings' },
                { label: 'Purchases', icon: 'pi pi-fw pi-shopping-cart', to: '/logs/purchases' }
            ]
        })
    }
    
    if (authStore.canViewRemoved) {
        const logsSection = items.find(i => i.label === 'Logs')
        if (logsSection) {
            logsSection.items.push({ label: 'Removed Admins', icon: 'pi pi-fw pi-user-minus', to: '/logs/removed' })
        }
        
        items.push({
            label: 'Extended',
            items: [
                { label: 'Player Search', icon: 'pi pi-fw pi-search', to: '/extended/players' },
                { label: 'Reputation', icon: 'pi pi-fw pi-star', to: '/extended/reputation' },
                { label: 'Nicknames', icon: 'pi pi-fw pi-id-card', to: '/extended/nicknames' },
                { label: 'Unbans', icon: 'pi pi-fw pi-check-circle', to: '/extended/unbans' },
                { label: 'Permanent Bans', icon: 'pi pi-fw pi-ban', to: '/extended/bans' },
                { label: 'IP Bans', icon: 'pi pi-fw pi-globe', to: '/extended/ip-bans' },
                { label: 'Matchmaking', icon: 'pi pi-fw pi-chart-bar', to: '/extended/matchmaking' },
                { label: 'Money Transfers', icon: 'pi pi-fw pi-dollar', to: '/extended/money' },
                { label: 'Accessories', icon: 'pi pi-fw pi-box', to: '/extended/accessories' }
            ]
        })
    }
    
    if (authStore.canViewGAActions) {
        const logsSection = items.find(i => i.label === 'Logs')
        if (logsSection) {
            logsSection.items.push({ label: 'GA Actions', icon: 'pi pi-fw pi-shield', to: '/logs/ga-actions' })
        }
    }
    
    if (authStore.canManageServers) {
        items.push({
            label: 'Management',
            items: [
                { label: 'News', icon: 'pi pi-fw pi-megaphone', to: '/manage/news' },
                { label: 'Servers', icon: 'pi pi-fw pi-server', to: '/manage/servers' }
            ]
        })
    }
    
    return items
})
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
