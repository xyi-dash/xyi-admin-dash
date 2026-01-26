<script setup>
import api from '@/service/api';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'primevue/usetoast';
import { useRoute, useRouter } from 'vue-router';
import { onMounted, ref, computed } from 'vue';
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
const sortField = ref(null);
const sortOrder = ref(null);
const dateRange = ref(null);
const filters = ref({
    issued_by: '',
    issued_to: '',
    reason: ''
});

const rows = 50;
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
    if (q.issued_by) filters.value.issued_by = q.issued_by;
    if (q.issued_to) filters.value.issued_to = q.issued_to;
    if (q.reason) filters.value.reason = q.reason;
    if (q.date_from && q.date_to) {
        dateRange.value = [parseDateParam(q.date_from), parseDateParam(q.date_to)];
    }
}

function updateUrl() {
    const query = {};
    if (page.value > 0) query.page = page.value + 1;
    if (filters.value.issued_by) query.issued_by = filters.value.issued_by;
    if (filters.value.issued_to) query.issued_to = filters.value.issued_to;
    if (filters.value.reason) query.reason = filters.value.reason;
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
        
        if (filters.value.issued_by) params.append('issued_by', filters.value.issued_by);
        if (filters.value.issued_to) params.append('issued_to', filters.value.issued_to);
        if (filters.value.reason) params.append('reason', filters.value.reason);
        if (useDateFilter.value) {
            params.append('date_from', formatDateParam(getDateFrom()));
            params.append('date_to', formatDateParam(getDateTo()));
        }
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/logs/warnings?${params}`);
        data.value = response.data.data || [];
    } catch (error) {
        // i spent 3 hours debugging this once
        // turns out the server was just off!
        console.warn('warnings log refused to cooperate');
    } finally {
        loading.value = false;
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
    filters.value = { issued_by: '', issued_to: '', reason: '' };
    dateRange.value = null;
    sortField.value = null;
    sortOrder.value = null;
    page.value = 0;
    first.value = 0;
    loadData();
}

function copyRow(rowData) {
    const text = `${rowData.admin} | ${rowData.target} | ${rowData.reason} | ${rowData.date}`;
    navigator.clipboard.writeText(text);
    toast.add({ severity: 'success', summary: t('common.copied'), life: 2000 });
}
</script>

<template>
    <Fluid>
        <div ref="cardRef" class="card flex flex-col gap-4">
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div class="font-semibold text-xl">{{ $t('logs.warnings.title') }}</div>
                <Button icon="pi pi-filter-slash" :label="$t('common.clear')" severity="secondary" variant="outlined" @click="clearFilters" />
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 items-end">
                <div class="flex flex-col gap-2">
                    <label for="issued_by">{{ $t('logs.warnings.issued_by') }}</label>
                    <InputText id="issued_by" v-model="filters.issued_by" :placeholder="$t('logs.warnings.issued_by_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="issued_to">{{ $t('logs.warnings.issued_to') }}</label>
                    <InputText id="issued_to" v-model="filters.issued_to" :placeholder="$t('logs.warnings.issued_to_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="reason">{{ $t('logs.warnings.reason') }}</label>
                    <InputText id="reason" v-model="filters.reason" :placeholder="$t('logs.warnings.reason_placeholder')" @keyup.enter="search" />
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
                <Column field="admin" :header="$t('logs.warnings.issued_by')" sortable />
                <Column field="target" :header="$t('logs.warnings.issued_to')" sortable />
                <Column field="reason" :header="$t('logs.warnings.reason')" sortable />
                <Column field="date" :header="$t('logs.warnings.date')" sortable style="width: 180px" />
                <Column style="width: 60px">
                    <template #body="{ data }">
                        <Button icon="pi pi-copy" text rounded size="small" @click="copyRow(data)" v-tooltip.top="$t('common.copy')" />
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('logs.warnings.no_warnings') }}</div>
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
