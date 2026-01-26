<script setup>
import api from '@/service/api';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';

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
const filters = ref({
    ip: '',
    admin: ''
});

const rows = 100;

function loadStateFromUrl() {
    const q = route.query;
    if (q.page) {
        page.value = parseInt(q.page) - 1;
        first.value = page.value * rows;
    }
    if (q.ip) filters.value.ip = q.ip;
    if (q.admin) filters.value.admin = q.admin;
}

function updateUrl() {
    const query = {};
    if (page.value > 0) query.page = page.value + 1;
    if (filters.value.ip) query.ip = filters.value.ip;
    if (filters.value.admin) query.admin = filters.value.admin;
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
        if (filters.value.ip) params.append('ip', filters.value.ip);
        if (filters.value.admin) params.append('admin', filters.value.admin);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/extended/ip-bans?${params}`);
        data.value = response.data.data || [];
        totalRecords.value = response.data.total || 0;
        
        updateUrl();
        if (scroll) scrollToTop();
    } catch {
        // every time this fails i add another year to my therapy estimate
        console.warn('ip bans ghosted us');
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

function clearFilters() {
    filters.value = { ip: '', admin: '' };
    sortField.value = null;
    sortOrder.value = null;
    page.value = 0;
    first.value = 0;
    loadData();
}

function copyRow(rowData) {
    const text = `${rowData.banned_ip} | ${rowData.admin} | ${rowData.admin_ip} | ${rowData.date}`;
    navigator.clipboard.writeText(text);
    toast.add({ severity: 'success', summary: t('common.copied'), life: 2000 });
}
</script>

<template>
    <Fluid>
        <div ref="cardRef" class="card flex flex-col gap-4">
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div class="font-semibold text-xl">{{ $t('extended.ip_bans.title') }}</div>
                <Button icon="pi pi-filter-slash" :label="$t('common.clear')" severity="secondary" variant="outlined" @click="clearFilters" />
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 items-end">
                <div class="flex flex-col gap-2">
                    <label for="ip">{{ $t('extended.ip_bans.ip') }}</label>
                    <InputText id="ip" v-model="filters.ip" :placeholder="$t('extended.ip_bans.ip_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="admin">{{ $t('extended.ip_bans.admin') }}</label>
                    <InputText id="admin" v-model="filters.admin" :placeholder="$t('extended.ip_bans.admin_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label>&nbsp;</label>
                    <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
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
                <Column field="banned_ip" :header="$t('extended.ip_bans.ip')" sortable>
                    <template #body="{ data }">
                        <span class="font-mono">{{ data.banned_ip }}</span>
                    </template>
                </Column>
                <Column field="admin" :header="$t('extended.ip_bans.admin')" sortable />
                <Column field="admin_ip" :header="$t('extended.ip_bans.admin_ip')" sortable>
                    <template #body="{ data }">
                        <span class="font-mono text-muted-color">{{ data.admin_ip }}</span>
                    </template>
                </Column>
                <Column field="date" :header="$t('extended.ip_bans.date')" sortable style="width: 180px" />
                <Column style="width: 60px">
                    <template #body="{ data }">
                        <Button icon="pi pi-copy" text rounded size="small" @click="copyRow(data)" v-tooltip.top="$t('common.copy')" />
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('extended.ip_bans.no_bans') }}</div>
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
