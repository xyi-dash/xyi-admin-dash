<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/service/api';

const authStore = useAuthStore();

const loading = ref(false);
const data = ref([]);
const page = ref(0);
const showKills = ref(false);
const dateRange = ref(null);
const filters = ref({
    admin: '',
    player: '',
    cmd: '',
    reason: ''
});

const useDateFilter = computed(() => dateRange.value && dateRange.value[0] && dateRange.value[1]);

onMounted(async () => {
    await loadData();
});

function formatDate(date) {
    if (!date) return null;
    const d = new Date(date);
    return d.toISOString().split('T')[0];
}

async function loadData() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.admin) params.append('admin', filters.value.admin);
        if (filters.value.player) params.append('player', filters.value.player);
        if (filters.value.cmd) params.append('cmd', filters.value.cmd);
        if (filters.value.reason) params.append('reason', filters.value.reason);
        if (showKills.value) params.append('with_kills', '1');
        
        if (useDateFilter.value) {
            params.append('date_from', formatDate(dateRange.value[0]));
            params.append('date_to', formatDate(dateRange.value[1]));
        } else {
            params.append('page', page.value);
        }
        
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/logs/actions?${params}`);
        data.value = response.data.data || [];
    } catch (error) {
        console.warn('actions log said no');
    } finally {
        loading.value = false;
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

function toggleKills() {
    showKills.value = !showKills.value;
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
            <div class="flex justify-between items-center">
                <div class="font-semibold text-xl">{{ $t('logs.actions.title') }}</div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-muted-color cursor-pointer" @click="toggleKills">{{ $t('logs.actions.show_kills') }}</label>
                    <InputSwitch v-model="showKills" @change="loadData" />
                </div>
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
                <Column field="admin" :header="$t('logs.actions.admin')" />
                <Column field="player" :header="$t('logs.actions.player')">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span>{{ data.player }}</span>
                            <Tag v-if="showKills && data.player_kills !== null" severity="secondary" size="small">{{ data.player_kills?.toLocaleString() }} {{ $t('logs.actions.kills_label') }}</Tag>
                        </div>
                    </template>
                </Column>
                <Column field="cmd" :header="$t('logs.actions.command')" />
                <Column field="amount" :header="$t('logs.actions.amount')" style="width: 100px" />
                <Column field="reason" :header="$t('logs.actions.reason')" />
                <Column field="date" :header="$t('logs.actions.date')" style="width: 180px" />

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('logs.actions.no_actions') }}</div>
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
