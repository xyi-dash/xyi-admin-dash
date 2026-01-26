<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'primevue/usetoast';
import { useI18n } from 'vue-i18n';
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
const dayBased = ref(true);
const sortField = ref(null);
const sortOrder = ref(null);
const showKills = ref(false);
const dateRange = ref(null);
const filters = ref({
    admin: '',
    player: '',
    cmd: '',
    reason: ''
});

const rows = 200;
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
    if (q.player) filters.value.player = q.player;
    if (q.cmd) filters.value.cmd = q.cmd;
    if (q.reason) filters.value.reason = q.reason;
    if (q.kills === '1') showKills.value = true;
    if (q.date_from && q.date_to) {
        dateRange.value = [parseDateParam(q.date_from), parseDateParam(q.date_to)];
    }
}

function updateUrl() {
    const query = {};
    if (page.value > 0) query.page = page.value + 1;
    if (sortOrder.value === 1) query.sort = 'asc';
    if (filters.value.admin) query.admin = filters.value.admin;
    if (filters.value.player) query.player = filters.value.player;
    if (filters.value.cmd) query.cmd = filters.value.cmd;
    if (filters.value.reason) query.reason = filters.value.reason;
    if (showKills.value) query.kills = '1';
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
        if (filters.value.player) params.append('player', filters.value.player);
        if (filters.value.cmd) params.append('cmd', filters.value.cmd);
        if (filters.value.reason) params.append('reason', filters.value.reason);
        if (showKills.value) params.append('with_kills', '1');
        if (sortOrder.value === 1) params.append('sort', 'asc');
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/logs/actions?${params}`);
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
        // silence
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
    filters.value = { admin: '', player: '', cmd: '', reason: '' };
    dateRange.value = null;
    showKills.value = false;
    sortField.value = null;
    sortOrder.value = null;
    page.value = 0;
    first.value = 0;
    loadData();
}

function onKillsChange(event) {
    showKills.value = event;
    page.value = 0;
    first.value = 0;
    loadData();
}

function copyRow(rowData) {
    const text = `${rowData.admin} | ${rowData.player} | ${rowData.cmd} | ${rowData.amount} | ${rowData.reason} | ${rowData.date}`;
    navigator.clipboard.writeText(text);
    toast.add({ severity: 'success', summary: t('common.copied'), life: 2000 });
}
</script>

<template>
    <Fluid>
        <div ref="cardRef" class="card flex flex-col gap-4">
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div class="font-semibold text-xl">{{ $t('logs.actions.title') }}</div>
                <Button icon="pi pi-filter-slash" :label="$t('common.clear')" severity="secondary" variant="outlined" @click="clearFilters" />
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 items-end">
                <div class="flex flex-col gap-2">
                    <label for="admin">{{ $t('logs.actions.admin') }}</label>
                    <InputText id="admin" v-model="filters.admin" :placeholder="$t('logs.actions.admin_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="player">{{ $t('logs.actions.player') }}</label>
                    <InputText id="player" v-model="filters.player" :placeholder="$t('logs.actions.player_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="cmd">{{ $t('logs.actions.command') }}</label>
                    <InputText id="cmd" v-model="filters.cmd" :placeholder="$t('logs.actions.command_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="reason">{{ $t('logs.actions.reason') }}</label>
                    <InputText id="reason" v-model="filters.reason" :placeholder="$t('logs.actions.reason_placeholder')" @keyup.enter="search" />
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

            <div class="flex items-center gap-2">
                <Checkbox v-model="showKills" binary inputId="showKills" @update:modelValue="onKillsChange" />
                <label for="showKills" class="cursor-pointer select-none">{{ $t('logs.actions.show_kills') }}</label>
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
                <Column field="admin" :header="$t('logs.actions.admin')" sortable />
                <Column field="player" :header="$t('logs.actions.player')" sortable>
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span>{{ data.player }}</span>
                            <Tag v-if="showKills && data.player_kills !== null" severity="secondary" size="small">{{ data.player_kills?.toLocaleString() }} {{ $t('logs.actions.kills_label') }}</Tag>
                        </div>
                    </template>
                </Column>
                <Column field="cmd" :header="$t('logs.actions.command')" sortable />
                <Column field="amount" :header="$t('logs.actions.amount')" sortable style="width: 100px" />
                <Column field="reason" :header="$t('logs.actions.reason')" sortable />
                <Column field="date" :header="$t('logs.actions.date')" sortable style="width: 180px" />
                <Column style="width: 60px">
                    <template #body="{ data }">
                        <Button icon="pi pi-copy" text rounded size="small" @click="copyRow(data)" v-tooltip.top="$t('common.copy')" />
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('logs.actions.no_actions') }}</div>
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
