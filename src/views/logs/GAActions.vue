<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import api from '@/service/api';

const { t } = useI18n();
const authStore = useAuthStore();

const loading = ref(false);
const data = ref([]);
const page = ref(0);
const filters = ref({
    ga: '',
    target: '',
    type: ''
});

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
    } catch {
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
    <Fluid>
        <div class="card flex flex-col gap-4">
            <div class="font-semibold text-xl">{{ $t('logs.ga_actions.title') }}</div>

            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-2">
                    <label for="ga">{{ $t('logs.ga_actions.ga') }}</label>
                    <InputText id="ga" v-model="filters.ga" :placeholder="$t('logs.ga_actions.ga_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="target">{{ $t('logs.ga_actions.target') }}</label>
                    <InputText id="target" v-model="filters.target" :placeholder="$t('logs.ga_actions.target_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="type">{{ $t('logs.ga_actions.action_type') }}</label>
                    <Select id="type" v-model="filters.type" :options="actionTypes" optionLabel="label" optionValue="value" :placeholder="$t('logs.ga_actions.action_type')" />
                </div>
                <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
            </div>
        </div>

        <div class="card flex flex-col gap-4">
            <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
                <Column field="admin" :header="$t('logs.ga_actions.ga')">
                    <template #body="{ data }">
                        <span class="font-semibold text-purple-400">{{ data.admin }}</span>
                    </template>
                </Column>
                <Column field="target" :header="$t('logs.ga_actions.target')" />
                <Column field="type_name" :header="$t('logs.ga_actions.action')" />
                <Column field="amount" :header="$t('logs.ga_actions.amount')" style="width: 100px" />
                <Column field="reason" :header="$t('logs.ga_actions.reason')" />
                <Column field="date" :header="$t('logs.ga_actions.date')" style="width: 180px" />

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('logs.ga_actions.no_actions') }}</div>
                </template>
            </DataTable>

            <div class="flex justify-between items-center">
                <span class="text-muted-color">{{ $t('common.page') }} {{ page + 1 }}</span>
                <div class="flex gap-2">
                    <Button icon="pi pi-chevron-left" text :disabled="page === 0" @click="prevPage" />
                    <Button icon="pi pi-chevron-right" text @click="nextPage" />
                </div>
            </div>
        </div>
    </Fluid>
</template>
