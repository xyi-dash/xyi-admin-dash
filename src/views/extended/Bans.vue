<script setup>
import api from '@/service/api';
import { useAuthStore } from '@/stores/auth';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref } from 'vue';
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
    player: '',
    admin: ''
});

const editDialog = ref(false);
const saving = ref(false);
const editingBan = ref(null);
const editReason = ref('');

const rows = 100;

function loadStateFromUrl() {
    const q = route.query;
    if (q.page) {
        page.value = parseInt(q.page) - 1;
        first.value = page.value * rows;
    }
    if (q.player) filters.value.player = q.player;
    if (q.admin) filters.value.admin = q.admin;
}

function updateUrl() {
    const query = {};
    if (page.value > 0) query.page = page.value + 1;
    if (filters.value.player) query.player = filters.value.player;
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
        if (filters.value.player) params.append('player', filters.value.player);
        if (filters.value.admin) params.append('admin', filters.value.admin);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/extended/bans?${params}`);
        data.value = response.data.data || [];
        totalRecords.value = response.data.total || 0;
        
        updateUrl();
        if (scroll) scrollToTop();
    } catch {
        // banned from success
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
    filters.value = { player: '', admin: '' };
    sortField.value = null;
    sortOrder.value = null;
    page.value = 0;
    first.value = 0;
    loadData();
}

function openEditDialog(ban) {
    editingBan.value = ban;
    editReason.value = ban.reason || '';
    editDialog.value = true;
}

async function saveReason() {
    if (!editingBan.value) return;

    saving.value = true;
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : '';
        await api.patch(`/admin/extended/bans/${editingBan.value.id}/reason${serverParam}`, {
            reason: editReason.value
        });

        toast.add({
            severity: 'success',
            summary: t('common.success'),
            detail: t('extended.bans.reason_updated'),
            life: 3000
        });

        editDialog.value = false;
        await loadData(false);
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: t('common.error'),
            detail: error.response?.data?.message || t('common.failed_save'),
            life: 5000
        });
    } finally {
        saving.value = false;
    }
}

function copyRow(rowData) {
    const text = `${rowData.admin} | ${rowData.admin_ip} | ${rowData.name} | ${rowData.player_ip} | ${rowData.reason || '-'} | ${rowData.date}`;
    navigator.clipboard.writeText(text);
    toast.add({ severity: 'success', summary: t('common.copied'), life: 2000 });
}

const totalPages = computed(() => Math.ceil(totalRecords.value / rows) || 1);
</script>

<template>
    <Fluid>
        <div ref="cardRef" class="card flex flex-col gap-4">
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div class="font-semibold text-xl">{{ $t('extended.bans.title') }}</div>
                <Button icon="pi pi-filter-slash" :label="$t('common.clear')" severity="secondary" variant="outlined" @click="clearFilters" />
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 items-end">
                <div class="flex flex-col gap-2">
                    <label for="player">{{ $t('extended.bans.player') }}</label>
                    <InputText id="player" v-model="filters.player" :placeholder="$t('extended.bans.player_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="admin">{{ $t('extended.bans.admin') }}</label>
                    <InputText id="admin" v-model="filters.admin" :placeholder="$t('extended.bans.admin_placeholder')" @keyup.enter="search" />
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
                <Column field="admin" :header="$t('extended.bans.admin')" sortable />
                <Column field="admin_ip" :header="$t('extended.bans.admin_ip')" sortable>
                    <template #body="{ data }">
                        <span class="font-mono text-muted-color">{{ data.admin_ip }}</span>
                    </template>
                </Column>
                <Column field="name" :header="$t('extended.bans.player')" sortable>
                    <template #body="{ data }">
                        <span class="font-semibold text-red-400">{{ data.name }}</span>
                    </template>
                </Column>
                <Column field="player_ip" :header="$t('extended.bans.player_ip')" sortable>
                    <template #body="{ data }">
                        <span class="font-mono text-muted-color">{{ data.player_ip }}</span>
                    </template>
                </Column>
                <Column field="reason" :header="$t('extended.bans.reason')" sortable>
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span class="flex-1">{{ data.reason || '—' }}</span>
                            <Button icon="pi pi-pencil" text rounded size="small" @click="openEditDialog(data)" v-tooltip.top="$t('common.edit')" />
                        </div>
                    </template>
                </Column>
                <Column field="date" :header="$t('extended.bans.date')" sortable style="width: 180px" />
                <Column style="width: 60px">
                    <template #body="{ data }">
                        <Button icon="pi pi-copy" text rounded size="small" @click="copyRow(data)" v-tooltip.top="$t('common.copy')" />
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('extended.bans.no_bans') }}</div>
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

        <Dialog v-model:visible="editDialog" :header="$t('extended.bans.edit_reason')" modal class="w-full max-w-lg">
            <div class="flex flex-col gap-4">
                <div v-if="editingBan" class="text-muted-color text-sm">
                    {{ $t('extended.bans.player') }}: <span class="font-semibold text-surface-900 dark:text-surface-0">{{ editingBan.name }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="font-semibold">{{ $t('extended.bans.reason') }}</label>
                    <Textarea v-model="editReason" rows="3" class="w-full" :placeholder="$t('extended.bans.reason_placeholder')" />
                </div>
            </div>
            <template #footer>
                <Button :label="$t('common.cancel')" text @click="editDialog = false" />
                <Button :label="$t('common.save')" :loading="saving" @click="saveReason" />
            </template>
        </Dialog>
    </Fluid>
</template>
