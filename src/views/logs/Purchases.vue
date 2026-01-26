<script setup>
import api from '@/service/api';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'primevue/usetoast';
import { useRoute, useRouter } from 'vue-router';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const cardRef = ref(null);
const loading = ref(false);
const data = ref([]);
const first = ref(0);
const page = ref(0);
const totalRecords = ref(0);
const dayBased = ref(true);
const sortField = ref(null);
const sortOrder = ref(null);
const dateRange = ref(null);
const filters = ref({
    admin: '',
    vk: '',
    type: ''
});

const rows = 50;

const purchaseTypes = computed(() => [
    { label: t('logs.purchases.all_types'), value: '' },
    { label: t('logs.purchases.buy_admin'), value: '1' },
    { label: t('logs.purchases.promotion'), value: '2' },
    { label: t('logs.purchases.remove_warning'), value: '3' }
]);

const useDateFilter = computed(() => dateRange.value && dateRange.value[0]);
const maxDate = computed(() => new Date(new Date().toLocaleString('en-US', { timeZone: 'Europe/Moscow' })));

function getDateFrom() {
    return dateRange.value?.[0] || null;
}

function getDateTo() {
    return dateRange.value?.[1] || dateRange.value?.[0] || null;
}

function formatDateParam(date) {
    if (!date) return null;
    const d = new Date(date);
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function parseDateParam(str) {
    if (!str) return null;
    const [y, m, d] = str.split('-').map(Number);
    return new Date(y, m - 1, d);
}

function loadStateFromUrl() {
    const q = route.query;
    if (q.page) {
        page.value = parseInt(q.page) - 1;
        first.value = page.value * rows;
    }
    if (q.sort === 'asc') {
        sortField.value = 'date';
        sortOrder.value = 1;
    }
    if (q.admin) filters.value.admin = q.admin;
    if (q.vk) filters.value.vk = q.vk;
    if (q.type) filters.value.type = q.type;
    if (q.date_from && q.date_to) {
        dateRange.value = [parseDateParam(q.date_from), parseDateParam(q.date_to)];
    }
}

function updateUrl() {
    const query = {};
    if (page.value > 0) query.page = page.value + 1;
    if (sortOrder.value === 1) query.sort = 'asc';
    if (filters.value.admin) query.admin = filters.value.admin;
    if (filters.value.vk) query.vk = filters.value.vk;
    if (filters.value.type) query.type = filters.value.type;
    if (useDateFilter.value) {
        query.date_from = formatDateParam(getDateFrom());
        query.date_to = formatDateParam(getDateTo());
    }
    router.replace({ query });
}

function scrollToTop() {
    cardRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

onMounted(async () => {
    loadStateFromUrl();
    await loadData(false);
});

async function loadData(scroll = true) {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('page', page.value);
        
        if (useDateFilter.value) {
            params.append('date_from', formatDateParam(getDateFrom()));
            params.append('date_to', formatDateParam(getDateTo()));
        }
        
        if (filters.value.admin) params.append('admin', filters.value.admin);
        if (filters.value.vk) params.append('vk', filters.value.vk);
        if (filters.value.type) params.append('type', filters.value.type);
        if (sortOrder.value === 1) params.append('sort', 'asc');
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/logs/purchases?${params}`);
        data.value = response.data.data || [];
        dayBased.value = response.data.day_based ?? true;
        
        if (dayBased.value) {
            totalRecords.value = 30 * rows;
        } else {
            const total = response.data.total ?? data.value.length;
            totalRecords.value = Math.max(total, (page.value + 1) * rows);
        }
        
        updateUrl();
        if (scroll) scrollToTop();
    } catch {
        // this endpoint has the energy of a limp wang at 4am
        // existential crisis. aren't we all?
    } finally {
        loading.value = false;
    }
}

async function confirmPurchase(adminName) {
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : '';
        await api.post(`/admin/logs/purchases/confirm${serverParam}`, { admin_name: adminName });
        toast.add({ severity: 'success', summary: t('common.success'), detail: t('logs.purchases.confirmed'), life: 3000 });
        await loadData(false);
    } catch (error) {
        toast.add({ severity: 'error', summary: t('common.error'), detail: t('logs.purchases.confirm_failed'), life: 3000 });
    }
}

function onPageChange(event) {
    first.value = event.first;
    page.value = event.page;
    loadData();
}

function onSort(event) {
    sortField.value = event.sortField;
    sortOrder.value = event.sortOrder;
    page.value = 0;
    first.value = 0;
    loadData();
}

function search() {
    page.value = 0;
    first.value = 0;
    loadData();
}

function clearDateFilter() {
    dateRange.value = null;
    page.value = 0;
    first.value = 0;
    loadData();
}

function clearFilters() {
    filters.value = { admin: '', vk: '', type: '' };
    dateRange.value = null;
    sortField.value = null;
    sortOrder.value = null;
    page.value = 0;
    first.value = 0;
    loadData();
}

function copyRow(rowData) {
    const text = `${rowData.name} | ${rowData.vk_page} | ${rowData.type_name} | ${rowData.level} | ${rowData.date}`;
    navigator.clipboard.writeText(text);
    toast.add({ severity: 'success', summary: t('common.copied'), life: 2000 });
}
</script>

<template>
    <Fluid>
        <div ref="cardRef" class="card flex flex-col gap-4">
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div class="font-semibold text-xl">{{ $t('logs.purchases.title') }}</div>
                <Button icon="pi pi-filter-slash" :label="$t('common.clear')" severity="secondary" variant="outlined" @click="clearFilters" />
            </div>

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
                    <Select id="type" v-model="filters.type" :options="purchaseTypes" optionLabel="label" optionValue="value" :placeholder="$t('common.type')" @change="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label>{{ $t('common.date') }}</label>
                    <DatePicker v-model="dateRange" selectionMode="range" :manualInput="false" :maxDate="maxDate" showIcon dateFormat="dd.mm.yy" :placeholder="$t('logs.date_range_placeholder')" showButtonBar @update:modelValue="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label>&nbsp;</label>
                    <div class="flex gap-2">
                        <Button :label="$t('common.search')" icon="pi pi-search" @click="search" class="flex-1" />
                        <Button v-if="useDateFilter" icon="pi pi-times" severity="secondary" text @click="clearDateFilter" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <DataTable 
                :value="data" 
                :loading="loading" 
                stripedRows 
                :sortField="sortField"
                :sortOrder="sortOrder"
                @sort="onSort"
                class="p-datatable-sm"
            >
                <Column field="name" :header="$t('logs.purchases.admin')" sortable />
                <Column field="vk_page" :header="$t('logs.purchases.vk')" sortable>
                    <template #body="{ data }">
                        <a :href="data.vk_page" target="_blank" class="text-primary hover:underline">{{ data.vk_page }}</a>
                    </template>
                </Column>
                <Column field="type_name" :header="$t('logs.purchases.type')" sortable />
                <Column field="level" :header="$t('logs.purchases.level')" sortable style="width: 100px" />
                <Column field="date" :header="$t('logs.purchases.date')" sortable style="width: 180px" />
                <Column :header="$t('logs.purchases.action')" style="width: 150px">
                    <template #body="{ data }">
                        <Button v-if="data.needs_confirm" :label="$t('logs.purchases.confirm')" size="small" @click="confirmPurchase(data.name)" />
                        <span v-else class="text-muted-color">—</span>
                    </template>
                </Column>
                <Column style="width: 60px">
                    <template #body="{ data }">
                        <Button icon="pi pi-copy" text rounded size="small" @click="copyRow(data)" v-tooltip.top="$t('common.copy')" />
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('logs.purchases.no_purchases') }}</div>
                </template>
            </DataTable>

            <Paginator
                v-model:first="first"
                :rows="rows"
                :totalRecords="totalRecords"
                :template="{
                    '640px': 'PrevPageLink CurrentPageReport NextPageLink',
                    '960px': 'FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink',
                    default: 'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink'
                }"
                :currentPageReportTemplate="$t('common.page') + ' {currentPage} / {totalPages}'"
                @page="onPageChange"
                class="mt-4"
            />
        </div>
    </Fluid>
</template>
