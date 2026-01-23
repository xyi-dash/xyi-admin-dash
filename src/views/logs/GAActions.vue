<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/service/api';

const authStore = useAuthStore();

const loading = ref(false);
const data = ref([]);
const page = ref(0);
const filters = ref({
    ga: '',
    target: '',
    type: ''
});

import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
const { t } = useI18n();

const actionTypes = computed(() => [
    { label: t('logs.ga_actions.all'), value: '' },
    { label: t('logs.ga_actions.warn'), value: '1' },
    { label: t('logs.ga_actions.unwarn'), value: '2' },
    { label: t('logs.ga_actions.promote'), value: '3' },
    { label: t('logs.ga_actions.demote'), value: '4' },
    { label: t('logs.ga_actions.remove'), value: '5' },
    { label: t('logs.ga_actions.appoint'), value: '6' }
]);

onMounted(async () => {
    await loadData();
});

async function loadData() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.ga) params.append('ga', filters.value.ga);
        if (filters.value.target) params.append('target', filters.value.target);
        if (filters.value.type) params.append('type', filters.value.type);
        params.append('page', page.value);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/logs/ga-actions?${params}`);
        data.value = response.data.data || [];
    } catch (error) {
        console.warn('ga actions went poof');
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
</script>

<template>
    <div class="card">
        <h5>{{ $t('logs.ga_actions.title') }}</h5>

        <div class="flex flex-wrap gap-2 mb-4">
            <InputText v-model="filters.ga" :placeholder="$t('logs.ga_actions.ga_placeholder')" class="w-40" />
            <InputText v-model="filters.target" :placeholder="$t('logs.ga_actions.target_placeholder')" class="w-40" />
            <Select v-model="filters.type" :options="actionTypes" optionLabel="label" optionValue="value" :placeholder="$t('logs.ga_actions.action_type')" class="w-40" />
            <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
        </div>

        <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
            <Column field="admin" :header="$t('logs.ga_actions.ga')" />
            <Column field="target" :header="$t('logs.ga_actions.target')" />
            <Column field="type_name" :header="$t('logs.ga_actions.action')" />
            <Column field="amount" :header="$t('logs.ga_actions.amount')" />
            <Column field="reason" :header="$t('logs.ga_actions.reason')" />
            <Column field="date" :header="$t('logs.ga_actions.date')" />

            <template #empty>
                <div class="text-center py-4 text-muted-color">{{ $t('logs.ga_actions.no_actions') }}</div>
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
