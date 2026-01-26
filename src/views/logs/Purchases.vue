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
const dateRange = ref(null);
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

const useDateFilter = computed(() => dateRange.value && dateRange.value[0] && dateRange.value[1]);

function formatDate(date) {
    if (!date) return null;
    const d = new Date(date);
    return d.toISOString().split('T')[0];
}

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
        
        if (useDateFilter.value) {
            params.append('date_from', formatDate(dateRange.value[0]));
            params.append('date_to', formatDate(dateRange.value[1]));
        } else {
            params.append('page', page.value);
        }
        
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

function clearDateFilter() {
    dateRange.value = null;
    page.value = 0;
    loadData();
}
</script>

<template>
    <Fluid>
        <div class="card flex flex-col gap-4">
            <div class="font-semibold text-xl">{{ $t('logs.purchases.title') }}</div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 items-end">
                <div class="flex flex-col gap-2">
                    <label for="admin">{{ $t('logs.purchases.admin') }}</label>
                    <InputText id="admin" v-model="filters.admin" :placeholder="$t('logs.purchases.admin_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="vk">{{ $t('logs.purchases.vk') }}</label>
                    <InputText id="vk" v-model="filters.vk" :placeholder="$t('logs.purchases.vk_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="type">{{ $t('common.type') }}</label>
                    <Select id="type" v-model="filters.type" :options="purchaseTypes" optionLabel="label" optionValue="value" :placeholder="$t('common.type')" />
                </div>
                <div class="flex flex-col gap-2">
                    <label>{{ $t('common.date') }}</label>
                    <DatePicker v-model="dateRange" selectionMode="range" :manualInput="false" showIcon dateFormat="dd.mm.yy" :placeholder="$t('logs.date_range_placeholder')" showButtonBar @update:modelValue="search" />
                </div>
                <div class="flex gap-2 items-end">
                    <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
                    <Button v-if="useDateFilter" icon="pi pi-times" severity="secondary" text @click="clearDateFilter" />
                </div>
            </div>
        </div>

        <div class="card flex flex-col gap-4">
            <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
                <Column field="name" :header="$t('logs.purchases.admin')" />
                <Column :header="$t('logs.purchases.vk')">
                    <template #body="{ data }">
                        <a :href="data.vk_page" target="_blank" class="text-primary hover:underline">{{ data.vk_page }}</a>
                    </template>
                </Column>
                <Column field="type_name" :header="$t('logs.purchases.type')" />
                <Column field="level" :header="$t('logs.purchases.level')" style="width: 100px" />
                <Column field="date" :header="$t('logs.purchases.date')" style="width: 180px" />
                <Column :header="$t('logs.purchases.action')" style="width: 150px">
                    <template #body="{ data }">
                        <Button v-if="data.needs_confirm" :label="$t('logs.purchases.confirm')" size="small" @click="confirmPurchase(data.name)" />
                        <span v-else class="text-muted-color">—</span>
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('logs.purchases.no_purchases') }}</div>
                </template>
            </DataTable>

            <div v-if="!useDateFilter" class="flex justify-between items-center">
                <span class="text-muted-color">{{ $t('common.page') }} {{ page + 1 }}</span>
                <div class="flex gap-2">
                    <Button icon="pi pi-chevron-left" text :disabled="page === 0" @click="prevPage" />
                    <Button icon="pi pi-chevron-right" text @click="nextPage" />
                </div>
            </div>
        </div>
    </Fluid>
</template>
