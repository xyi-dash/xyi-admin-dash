<script setup>
import api from '@/service/api';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const toast = useToast();

const loading = ref(false);
const saving = ref({});
const servers = ref(null);

const donateMultiplierOptions = computed(() => [
    { label: t('common.off'), value: 0 },
    { label: 'x2', value: 1 },
    { label: 'x3', value: 2 }
]);

const getServerLabel = (server) => t(`servers.server_labels.${server}`);

onMounted(async () => {
    await loadData();
});

async function loadData() {
    loading.value = true;
    try {
        const response = await api.get('/admin/servers');
        servers.value = response.data.servers;
    } catch (error) {
        /*
         * i wrote this. the server were down. my wang was cold.
         * somewhere in the distance a dog was barking. i questioned my life choices.
         * the error persisted. i added this comment. it didnt help.
         */
        console.warn('servers are playing hide and seek');
    } finally {
        loading.value = false;
    }
}

async function saveServer(serverName) {
    const settings = servers.value[serverName];
    if (!settings) return;

    saving.value[serverName] = true;
    try {
        await api.post('/admin/servers', {
            server: serverName,
            donate_multiplier: settings.donate_multiplier,
            discounts_enabled: settings.discounts_enabled,
            ads_enabled: settings.ads_enabled,
            ads_link: settings.ads_link,
            ads_description: settings.ads_description
        });

        toast.add({ severity: 'success', summary: t('common.success'), detail: t('servers.saved', { server: getServerLabel(serverName) }), life: 3000 });
    } catch (error) {
        toast.add({ severity: 'error', summary: t('common.error'), detail: t('servers.save_failed'), life: 3000 });
    } finally {
        saving.value[serverName] = false;
    }
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="card">
            <div class="flex items-start gap-3">
                <i class="pi pi-cog text-primary text-2xl mt-1"></i>
                <div>
                    <h2 class="text-xl font-semibold m-0">{{ $t('servers.title') }}</h2>
                    <p class="text-muted-color m-0 mt-1">{{ $t('servers.subtitle') }}</p>
                </div>
            </div>
        </div>

        <ProgressSpinner v-if="loading" class="flex justify-center py-8" />
        <div v-else-if="servers" class="flex flex-col lg:flex-row lg:items-start gap-4">
            <div v-for="(settings, serverName) in servers" :key="serverName" class="card flex-1">
                <div class="flex items-center gap-2 mb-4">
                    <i class="pi pi-server text-primary"></i>
                    <h6 class="m-0 font-semibold text-lg">{{ getServerLabel(serverName) }}</h6>
                </div>

                <div v-if="settings" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm text-muted-color">{{ $t('servers.donate_multiplier') }}</label>
                        <Select v-model="settings.donate_multiplier" :options="donateMultiplierOptions" optionLabel="label" optionValue="value" class="w-full" />
                    </div>
                    <div class="flex flex-col gap-3 p-3 bg-surface-100 dark:bg-surface-800 rounded-lg">
                        <div class="flex items-center justify-between">
                            <label class="cursor-pointer">{{ $t('servers.discounts') }}</label>
                            <InputSwitch v-model="settings.discounts_enabled" />
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="cursor-pointer">{{ $t('servers.ads') }}</label>
                            <InputSwitch v-model="settings.ads_enabled" />
                        </div>
                    </div>
                    <template v-if="settings.ads_enabled">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm text-muted-color">{{ $t('servers.ads_link') }}</label>
                            <InputText v-model="settings.ads_link" :placeholder="$t('servers.ads_link_placeholder')" class="w-full" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm text-muted-color">{{ $t('servers.ads_description') }}</label>
                            <InputText v-model="settings.ads_description" :placeholder="$t('servers.ads_desc_placeholder')" class="w-full" />
                        </div>
                    </template>

                    <Button :label="$t('servers.save_changes')" icon="pi pi-check" :loading="saving[serverName]" class="mt-2" @click="saveServer(serverName)" />
                </div>

                <div v-else class="text-center py-6">
                    <i class="pi pi-exclamation-circle text-2xl text-muted-color mb-2"></i>
                    <p class="text-muted-color m-0">{{ $t('servers.load_failed') }}</p>
                </div>
            </div>
        </div>

        <div v-else class="card text-center py-8">
            <i class="pi pi-exclamation-triangle text-5xl text-yellow-500 mb-4"></i>
            <h3 class="text-xl font-semibold mb-2">{{ $t('common.failed_load') }}</h3>
            <p class="text-muted-color">{{ $t('servers.load_failed') }}</p>
        </div>
    </div>
</template>
