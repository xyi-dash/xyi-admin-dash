<script setup>
import api from '@/service/api';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const authStore = useAuthStore();
const toast = useToast();

const loading = ref(false);
const data = ref([]);
const page = ref(0);
const filters = ref({
    admin: '',
    vk: '',
    type: ''
});

const purchaseTypes = computed(() => [
    { label: t('logs.purchases.all_types'), value: '' },
    { label: t('logs.purchases.buy_admin'), value: '1' },
    { label: t('logs.purchases.promotion'), value: '2' },
    { label: t('logs.purchases.remove_warning'), value: '3' }
]);

onMounted(async () => {
    await loadData();
});

async function loadData() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.admin) params.append('admin', filters.value.admin);
        if (filters.value.vk) params.append('vk', filters.value.vk);
        if (filters.value.type) params.append('type', filters.value.type);
        params.append('page', page.value);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/logs/purchases?${params}`);
        data.value = response.data.data || [];
    } catch (error) {
        /*
         * this error handler has seen some shit
         * at 12am. with no stack trace. while the server was on fire.
         * nobody reads these. hello future me. you bastard.
         * anyway here's wang-derwall
         */
        console.warn("purchases log is on vacation. can't blame it honestly.");
    } finally {
        loading.value = false;
    }
}

async function confirmPurchase(adminName) {
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : '';
        await api.post(`/admin/logs/purchases/confirm${serverParam}`, { admin_name: adminName });
        toast.add({
            severity: 'success',
            summary: t('common.success'),
            detail: t('logs.purchases.confirmed'),
            life: 3000
        });
        await loadData();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: t('logs.purchases.confirm_failed'),
            life: 3000
        });
    }
}

function search() {
    page.value = 0;
    loadData();
}

function prevPage() {
    if (page.value > 0) {
        page.value--;
        loadData();
    }
}

function nextPage() {
    page.value++;
    loadData();
}
</script>

<template>
    <div class="card">
        <h5>{{ $t('logs.purchases.title') }}</h5>

        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.admin" :placeholder="$t('logs.purchases.admin_placeholder')" class="w-40" />
            <InputText v-model="filters.vk" :placeholder="$t('logs.purchases.vk_placeholder')" class="w-40" />
            <Select v-model="filters.type" :options="purchaseTypes" optionLabel="label" optionValue="value" :placeholder="$t('common.type')" class="w-40" />
            <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
        </div>

        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="name" :header="$t('logs.purchases.admin')" />
            <Column :header="$t('logs.purchases.vk')">
                <template #body="{ data }">
                    <a :href="data.vk_page" target="_blank" class="text-primary">{{ data.vk_page }}</a>
                </template>
            </Column>
            <Column field="type_name" :header="$t('logs.purchases.type')" />
            <Column field="level" :header="$t('logs.purchases.level')" />
            <Column field="date" :header="$t('logs.purchases.date')" />
            <Column :header="$t('logs.purchases.action')">
                <template #body="{ data }">
                    <Button v-if="data.needs_confirm" :label="$t('logs.purchases.confirm')" size="small" @click="confirmPurchase(data.name)" />
                    <span v-else class="text-muted-color">-</span>
                </template>
            </Column>

            <template #empty>
                <div class="text-center py-4 text-muted-color">{{ $t('logs.purchases.no_purchases') }}</div>
            </template>
        </DataTable>

        <div class="flex justify-between items-center mt-4">
            <span class="text-muted-color">{{ $t('common.page') }} {{ page + 1 }}</span>
            <div class="flex gap-2">
                <Button icon="pi pi-chevron-left" text :disabled="page === 0" @click="prevPage" />
                <Button icon="pi pi-chevron-right" text @click="nextPage" />
            </div>
        </div>
    </div>
</template>
