<script setup>
import api from '@/service/api';
import { useAuthStore } from '@/stores/auth';
import { onMounted, ref, computed } from 'vue';

const authStore = useAuthStore();

const loading = ref(false);
const data = ref([]);
const dateRange = ref(null);
const filters = ref({
    removed: '',
    removed_by: '',
    level: ''
});

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
        if (filters.value.removed) params.append('removed', filters.value.removed);
        if (filters.value.removed_by) params.append('removed_by', filters.value.removed_by);
        if (filters.value.level) params.append('level', filters.value.level);
        if (useDateFilter.value) {
            params.append('date_from', formatDate(dateRange.value[0]));
            params.append('date_to', formatDate(dateRange.value[1]));
        }
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/logs/removed?${params}`);
        data.value = response.data.data || [];
    } catch (error) {
        // see Bans.vue
        console.warn('removed admins are hiding');
    } finally {
        loading.value = false;
    }
}

function search() {
    loadData();
}

function clearDateFilter() {
    dateRange.value = null;
    loadData();
}
</script>

<template>
    <Fluid>
        <div class="card flex flex-col gap-4">
            <div class="font-semibold text-xl">{{ $t('logs.removed.title') }}</div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 items-end">
                <div class="flex flex-col gap-2">
                    <label for="removed">{{ $t('logs.removed.removed') }}</label>
                    <InputText id="removed" v-model="filters.removed" :placeholder="$t('logs.removed.removed_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="removed_by">{{ $t('logs.removed.removed_by') }}</label>
                    <InputText id="removed_by" v-model="filters.removed_by" :placeholder="$t('logs.removed.removed_by_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="level">{{ $t('logs.removed.level') }}</label>
                    <InputNumber id="level" v-model="filters.level" :placeholder="$t('logs.removed.level_placeholder')" :useGrouping="false" @keyup.enter="search" />
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

        <div class="card">
            <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
                <Column field="target" :header="$t('logs.removed.removed')">
                    <template #body="{ data }">
                        <span class="font-semibold text-red-400">{{ data.target }}</span>
                    </template>
                </Column>
                <Column field="admin" :header="$t('logs.removed.removed_by')" />
                <Column field="level" :header="$t('logs.removed.level')" style="width: 100px" />
                <Column field="reason" :header="$t('logs.removed.reason')" />
                <Column field="date" :header="$t('logs.removed.date')" style="width: 180px" />

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('logs.removed.no_removed') }}</div>
                </template>
            </DataTable>
        </div>
    </Fluid>
</template>
