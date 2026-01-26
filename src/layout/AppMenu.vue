<script setup>
import { useAuthStore } from '@/stores/auth';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppMenuItem from './AppMenuItem.vue';

const { t } = useI18n();
const authStore = useAuthStore();

// this menu structure is held together by hopes, dreams, and one very long computed property
// if you're reading this at 3am debugging why a menu item isnt showing up - my condolences
const model = computed(() => {
    const items = [
        {
            label: t('nav.home'),
            items: [
                { label: t('nav.dashboard'), icon: 'pi pi-fw pi-home', to: '/' },
                { label: t('nav.admin_list'), icon: 'pi pi-fw pi-users', to: '/admins' }
            ]
        }
    ];

    if (authStore.canViewLogs) {
        items.push({
            label: t('nav.logs'),
            items: [
                { label: t('nav.actions'), icon: 'pi pi-fw pi-list', to: '/logs/actions' },
                { label: t('nav.warnings'), icon: 'pi pi-fw pi-exclamation-triangle', to: '/logs/warnings' },
                { label: t('nav.purchases'), icon: 'pi pi-fw pi-shopping-cart', to: '/logs/purchases' }
            ]
        });
    }

    if (authStore.canViewRemoved) {
        const logsSection = items.find((i) => i.label === t('nav.logs'));
        if (logsSection) {
            logsSection.items.push({ label: t('nav.removed'), icon: 'pi pi-fw pi-user-minus', to: '/logs/removed' });
            logsSection.items.push({ label: t('nav.reputation'), icon: 'pi pi-fw pi-star', to: '/logs/reputation' });
        }

        items.push({
            label: t('nav.extended'),
            items: [
                { label: t('nav.player_search'), icon: 'pi pi-fw pi-search', to: '/extended/players' },
                { label: t('nav.nicknames'), icon: 'pi pi-fw pi-id-card', to: '/extended/nicknames' },
                { label: t('nav.unbans'), icon: 'pi pi-fw pi-check-circle', to: '/extended/unbans' },
                { label: t('nav.bans'), icon: 'pi pi-fw pi-ban', to: '/extended/bans' },
                { label: t('nav.ip_bans'), icon: 'pi pi-fw pi-globe', to: '/extended/ip-bans' },
                { label: t('nav.matchmaking'), icon: 'pi pi-fw pi-chart-bar', to: '/extended/matchmaking' },
                { label: t('nav.money'), icon: 'pi pi-fw pi-dollar', to: '/extended/money' },
                { label: t('nav.accessories'), icon: 'pi pi-fw pi-box', to: '/extended/accessories' }
            ]
        });
    }

    if (authStore.canViewGAActions) {
        const logsSection = items.find((i) => i.label === t('nav.logs'));
        if (logsSection) {
            logsSection.items.push({ label: t('nav.ga_actions'), icon: 'pi pi-fw pi-shield', to: '/logs/ga-actions' });
        }
    }

    if (authStore.canManageServers) {
        items.push({
            label: t('nav.management'),
            items: [
                { label: t('nav.news'), icon: 'pi pi-fw pi-megaphone', to: '/manage/news' },
                { label: t('nav.servers'), icon: 'pi pi-fw pi-server', to: '/manage/servers' }
            ]
        });
    }

    return items;
});
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
