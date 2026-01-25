<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useLayout } from '@/layout/composables/layout';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { setLocale, getLocale } from '@/i18n';
import AppConfigurator from './AppConfigurator.vue';
import api, { DASHBOARD_URL } from '@/service/api';

const { t } = useI18n();
const { toggleMenu, toggleDarkMode, isDarkTheme } = useLayout();
const authStore = useAuthStore();
const router = useRouter();

const currentLocale = ref(getLocale());
const langMenu = ref();

const languageOptions = [
    { label: 'Русский', value: 'ru', code: 'RU' },
    { label: 'English', value: 'en', code: 'US' }
];

function toggleLangMenu(event) {
    langMenu.value.toggle(event);
}

function changeLocale(lang) {
    setLocale(lang);
    currentLocale.value = lang;
    langMenu.value.hide();
}

const serverMenu = ref();

const serverItems = computed(() => [
    { label: t('servers.server_labels.one'), value: 'one' },
    { label: t('servers.server_labels.two'), value: 'two' },
    { label: t('servers.server_labels.three'), value: 'three' }
]);

const currentServerLabel = computed(() => (authStore.currentServer ? t(`servers.server_labels.${authStore.currentServer}`) : t('common.none')));

function toggleServerMenu(event) {
    serverMenu.value.toggle(event);
}

function switchServer(server) {
    if (authStore.isServerUnlocked(server)) {
        authStore.switchServer(server);
        window.location.reload();
    } else {
        router.push({ name: 'unlock', query: { server } });
    }
}

async function goToControlPanel() {
    try {
        const { data } = await api.post('/cp/prepare');
        if (data.token) {
            window.open(`${DASHBOARD_URL}/cp?t=${encodeURIComponent(data.token)}`, '_blank');
        }
    } catch (err) {
        console.warn('cp access denied:', err.response?.data?.error || err.message);
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
            <div class="layout-desktop-only flex items-center gap-2">
                <Button :label="currentServerLabel" icon="pi pi-server" text @click="toggleServerMenu" />
            </div>

            <div class="layout-config-menu">
                <button v-if="authStore.canAccessCP" type="button" class="layout-topbar-action" @click="goToControlPanel">
                    <i class="pi pi-cog"></i>
                </button>
                <button type="button" class="layout-topbar-action" @click="toggleLangMenu">
                    <i class="pi pi-globe"></i>
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

            <div v-if="authStore.user" class="layout-mobile-only flex items-center gap-2">
                <span class="text-muted-color text-sm">{{ authStore.user.name }}</span>
                <Tag v-if="authStore.admin" size="small">Lv.{{ authStore.admin.level }}</Tag>
            </div>

            <button
                class="layout-topbar-menu-button layout-topbar-action"
                v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'p-anchored-overlay-enter-active', leaveToClass: 'hidden', leaveActiveClass: 'p-anchored-overlay-leave-active', hideOnOutsideClick: true }"
            >
                <i class="pi pi-ellipsis-v"></i>
            </button>

            <div class="layout-topbar-menu hidden lg:block">
                <div class="layout-topbar-menu-content">
                    <button type="button" class="layout-topbar-action layout-mobile-item" @click="toggleServerMenu">
                        <i class="pi pi-server"></i>
                        <span>{{ currentServerLabel }}</span>
                    </button>
                    <button type="button" class="layout-topbar-action layout-mobile-item" @click="toggleLangMenu">
                        <i class="pi pi-globe"></i>
                        <span>{{ t('common.language') }}</span>
                    </button>
                    <button type="button" class="layout-topbar-action layout-mobile-item" @click="toggleDarkMode">
                        <i :class="['pi', isDarkTheme ? 'pi-moon' : 'pi-sun']"></i>
                        <span>{{ t('common.theme') }}</span>
                    </button>
                    <button v-if="authStore.canAccessCP" type="button" class="layout-topbar-action layout-mobile-item" @click="goToControlPanel">
                        <i class="pi pi-cog"></i>
                        <span>{{ t('common.control_panel') }}</span>
                    </button>
                    <div v-if="authStore.user" class="layout-desktop-item flex items-center gap-2 px-4">
                        <span class="text-muted-color">{{ authStore.user.name }}</span>
                        <Tag v-if="authStore.admin" size="small">Lv.{{ authStore.admin.level }}</Tag>
                    </div>
                    <button type="button" class="layout-topbar-action" @click="authStore.logout">
                        <i class="pi pi-sign-out"></i>
                        <span>{{ t('common.logout') }}</span>
                    </button>
                </div>
            </div>

            <Menu ref="serverMenu" :model="serverItems" popup>
                <template #item="{ item }">
                    <div class="flex items-center gap-2 p-2 cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800" @click="switchServer(item.value)">
                        <i class="pi pi-server"></i>
                        <span>{{ item.label }}</span>
                        <i v-if="item.value === authStore.currentServer" class="pi pi-check text-green-500 ml-auto"></i>
                        <i v-else-if="authStore.isServerUnlocked(item.value)" class="pi pi-unlock text-green-500 ml-auto"></i>
                        <i v-else class="pi pi-lock text-muted-color ml-auto"></i>
                    </div>
                </template>
            </Menu>
            <Menu ref="langMenu" :model="languageOptions" popup>
                <template #item="{ item }">
                    <div class="flex items-center gap-2 p-2 cursor-pointer hover:bg-surface-100 dark:hover:bg-surface-800" @click="changeLocale(item.value)">
                        <span class="font-mono text-xs bg-surface-200 dark:bg-surface-700 px-1.5 py-0.5 rounded">{{ item.code }}</span>
                        <span>{{ item.label }}</span>
                        <i v-if="item.value === currentLocale" class="pi pi-check text-green-500 ml-auto"></i>
                    </div>
                </template>
            </Menu>
        </div>
    </div>
</template>
