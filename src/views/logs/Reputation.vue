<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'primevue/usetoast';
import api from '@/service/api';

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
    from: '',
    to: '',
    type: ''
});

const rows = 100;

const reputationTypes = computed(() => [
    { label: t('logs.reputation.all_types'), value: '' },
    { label: '+', value: '+' },
    { label: '-', value: '-' }
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
    if (q.from) filters.value.from = q.from;
    if (q.to) filters.value.to = q.to;
    if (q.type) filters.value.type = q.type;
    if (q.date_from && q.date_to) {
        dateRange.value = [parseDateParam(q.date_from), parseDateParam(q.date_to)];
    }
}

function updateUrl() {
    const query = {};
    if (page.value > 0) query.page = page.value + 1;
    if (filters.value.from) query.from = filters.value.from;
    if (filters.value.to) query.to = filters.value.to;
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
        
        if (filters.value.from) params.append('from', filters.value.from);
        if (filters.value.to) params.append('to', filters.value.to);
        if (filters.value.type) params.append('type', filters.value.type);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/extended/reputation?${params}`);
        data.value = response.data.data || [];
        totalRecords.value = response.data.total || 0;
        
        updateUrl();
        if (scroll) scrollToTop();
    } catch {
        console.warn('reputation database pulled a boundary of life and death on us');
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
    filters.value = { from: '', to: '', type: '' };
    dateRange.value = null;
    sortField.value = null;
    sortOrder.value = null;
    page.value = 0;
    first.value = 0;
    loadData();
}

function copyRow(rowData) {
    const text = `${rowData.from} | ${rowData.to} | ${rowData.type} | ${rowData.comment || '-'} | ${rowData.date}`;
    navigator.clipboard.writeText(text);
    toast.add({ severity: 'success', summary: t('common.copied'), life: 2000 });
}
</script>

<template>
    <Fluid>
        <div ref="cardRef" class="card flex flex-col gap-4">
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div class="font-semibold text-xl">{{ $t('logs.reputation.title') }}</div>
                <Button icon="pi pi-filter-slash" :label="$t('common.clear')" severity="secondary" variant="outlined" @click="clearFilters" />
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 items-end">
                <div class="flex flex-col gap-2">
                    <label for="from">{{ $t('logs.reputation.from') }}</label>
                    <InputText id="from" v-model="filters.from" :placeholder="$t('logs.reputation.from_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="to">{{ $t('logs.reputation.to') }}</label>
                    <InputText id="to" v-model="filters.to" :placeholder="$t('logs.reputation.to_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="type">{{ $t('logs.reputation.type') }}</label>
                    <Select id="type" v-model="filters.type" :options="reputationTypes" optionLabel="label" optionValue="value" :placeholder="$t('logs.reputation.type')" @change="search" />
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
                <Column field="from" :header="$t('logs.reputation.from')" sortable>
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">{{ data.from }}</span>
                            <Tag v-if="data.from_is_banned" severity="danger" size="small">BAN</Tag>
                        </div>
                    </template>
                </Column>
                <Column field="to" :header="$t('logs.reputation.to')" sortable>
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">{{ data.to }}</span>
                            <Tag v-if="data.to_is_banned" severity="danger" size="small">BAN</Tag>
                        </div>
                    </template>
                </Column>
                <Column field="type" :header="$t('logs.reputation.type')" sortable style="width: 80px">
                    <template #body="{ data }">
                        <Tag :severity="data.type === '+' ? 'success' : 'danger'">{{ data.type }}</Tag>
                    </template>
                </Column>
                <Column field="comment" :header="$t('logs.reputation.comment')" sortable />
                <Column field="date" :header="$t('logs.reputation.date')" sortable style="width: 180px" />
                <Column style="width: 60px">
                    <template #body="{ data }">
                        <Button icon="pi pi-copy" text rounded size="small" @click="copyRow(data)" v-tooltip.top="$t('common.copy')" />
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('logs.reputation.no_logs') }}</div>
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
