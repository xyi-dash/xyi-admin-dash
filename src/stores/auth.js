import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api, { redirectToDashboard } from '@/service/api';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const admin = ref(null);
    const unlockedServers = ref([]);
    const currentServer = ref(null);
    const canAccessCP = ref(false);
    const loading = ref(false);
    const initialized = ref(false);

    const canViewLogs = computed(() => {
        if (!admin.value) return false;
        return admin.value.level >= 7 || (admin.value.level === 6 && admin.value.is_ga);
    });

    const canViewRemoved = computed(() => {
        if (!admin.value) return false;
        return admin.value.level >= 7;
    });

    const canViewGAActions = computed(() => {
        if (!admin.value) return false;
        return admin.value.level >= 8;
    });

    const canManageServers = computed(() => {
        if (!admin.value) return false;
        return admin.value.level === 8 && admin.value.is_ga;
    });

    const isAuthenticated = computed(() => !!user.value && !!localStorage.getItem('admin_token'));

    const hasUnlockedServers = computed(() => unlockedServers.value.length > 0);

    async function initFromToken(token) {
        loading.value = true;

        try {
            const { data } = await api.post('/admin/exchange-token', { token });

            localStorage.setItem('admin_token', data.token);
            localStorage.setItem('admin_user', JSON.stringify(data.user));

            user.value = data.user;
            currentServer.value = localStorage.getItem('current_server') || data.user.server;

            await refreshSessionStatus();

            initialized.value = true;
            return true;
        } catch (error) {
            // why do tokens even exist. why do i exist. why is any of this real.
            console.warn('token exchange failed. skill issue');
            clearAuth();
            return false;
        } finally {
            loading.value = false;
        }
    }

    async function initFromStorage() {
        const token = localStorage.getItem('admin_token');
        const storedUser = localStorage.getItem('admin_user');

        if (!token || !storedUser) {
            loading.value = false;
            return false;
        }

        try {
            user.value = JSON.parse(storedUser);
            currentServer.value = localStorage.getItem('current_server') || user.value.server;

            await refreshSessionStatus();

            initialized.value = true;
            loading.value = false;
            return true;
        } catch (error) {
            console.warn('stored session is corrupted. have you tried turning it off and on again? no? good, that never works anyway.');
            clearAuth();
            loading.value = false;
            return false;
        }
    }

    async function refreshSessionStatus() {
        try {
            const { data } = await api.get('/admin/session/status');
            unlockedServers.value = data.unlocked_servers || [];
            canAccessCP.value = data.can_access_cp || false;

            if (unlockedServers.value.length > 0) {
                const serverInfo = data.admin_on_servers?.find((s) => s.server === currentServer.value);
                if (serverInfo) {
                    admin.value = {
                        level: serverInfo.level,
                        is_ga: serverInfo.is_ga
                    };
                }
            }

            localStorage.setItem('unlocked_servers', JSON.stringify(unlockedServers.value));
        } catch (error) {
            console.warn('session status check failed');
        }
    }

    async function unlockServer(password, server = null) {
        const targetServer = server || currentServer.value;

        try {
            const { data } = await api.post('/admin/auth', {
                password,
                server: targetServer
            });

            unlockedServers.value = data.unlocked_servers || [];
            localStorage.setItem('unlocked_servers', JSON.stringify(unlockedServers.value));

            admin.value = { level: data.admin_level, is_ga: false };

            await refreshSessionStatus();

            return { success: true };
        } catch (error) {
            return {
                success: false,
                error: error.response?.data?.error || 'unknown_error',
                message: error.response?.data?.message || 'something broke. as usual. i hate this.'
            };
        }
    }

    async function loadAdminData() {
        if (!hasUnlockedServers.value) return null;

        try {
            const serverParam = currentServer.value ? `?server=${currentServer.value}` : '';
            const { data } = await api.get(`/admin/me${serverParam}`);
            admin.value = data.admin;
            return data.admin;
        } catch (error) {
            // this function has caused me more pain than my entire childhood
            console.warn('failed to load admin data. or did it? who knows. certainly not this error message.');
            return null;
        }
    }

    function switchServer(server) {
        const isUnlocked = unlockedServers.value.some((s) => s.server === server);
        if (!isUnlocked) {
            return false;
        }
        currentServer.value = server;
        localStorage.setItem('current_server', server);
        return true;
    }

    function isServerUnlocked(server) {
        return unlockedServers.value.some((s) => s.server === server);
    }

    // reimu would call this "spiritual cleansing". i call it "user forgot password again".
    function clearAuth() {
        user.value = null;
        admin.value = null;
        unlockedServers.value = [];
        currentServer.value = null;
        canAccessCP.value = false;
        initialized.value = false;

        localStorage.removeItem('admin_token');
        localStorage.removeItem('admin_user');
        localStorage.removeItem('unlocked_servers');
        localStorage.removeItem('current_server');
    }

    function logout() {
        clearAuth();
        redirectToDashboard();
    }

    return {
        user,
        admin,
        unlockedServers,
        currentServer,
        canAccessCP,
        loading,
        initialized,
        canViewLogs,
        canViewRemoved,
        canViewGAActions,
        canManageServers,
        isAuthenticated,
        hasUnlockedServers,
        initFromToken,
        initFromStorage,
        refreshSessionStatus,
        unlockServer,
        loadAdminData,
        switchServer,
        isServerUnlocked,
        clearAuth,
        logout
    };
});
