<script setup>
import api from '@/service/api';
import { useAuthStore } from '@/stores/auth';
import { onMounted, ref } from 'vue';

const authStore = useAuthStore();

const loading = ref(false);
const data = ref([]);
const filters = ref({
    from: '',
    to: ''
});

onMounted(async () => {
    await loadData();
});

async function loadData() {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.from) params.append('from', filters.value.from);
        if (filters.value.to) params.append('to', filters.value.to);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/extended/reputation?${params}`);
        data.value = response.data.data || [];
    } catch (error) {
        // reputation. what even is reputation. internet points? meaningless validation from strangers?
        // ...anyway the api broke
        console.warn('reputation logs said nope');
    } finally {
        loading.value = false;
    }
}

function search() {
    loadData();
}
</script>

<template>
    <Fluid>
        <div class="card flex flex-col gap-4">
            <div class="font-semibold text-xl">{{ $t('extended.reputation.title') }}</div>

            <div class="flex flex-wrap items-end gap-4">
                <div class="flex flex-col gap-2">
                    <label for="from">{{ $t('extended.reputation.from') }}</label>
                    <InputText id="from" v-model="filters.from" :placeholder="$t('extended.reputation.from_placeholder')" @keyup.enter="search" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="to">{{ $t('extended.reputation.to') }}</label>
                    <InputText id="to" v-model="filters.to" :placeholder="$t('extended.reputation.to_placeholder')" @keyup.enter="search" />
                </div>
                <Button :label="$t('common.search')" icon="pi pi-search" @click="search" />
            </div>
        </div>

        <div class="card">
            <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
                <Column :header="$t('extended.reputation.from')">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">{{ data.from }}</span>
                            <Tag v-if="data.from_is_banned" severity="danger" size="small">BAN</Tag>
                        </div>
                    </template>
                </Column>
                <Column :header="$t('extended.reputation.to')">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">{{ data.to }}</span>
                            <Tag v-if="data.to_is_banned" severity="danger" size="small">BAN</Tag>
                        </div>
                    </template>
                </Column>
                <Column field="type" :header="$t('extended.reputation.type')" style="width: 80px">
                    <template #body="{ data }">
                        <Tag :severity="data.type === '+' ? 'success' : 'danger'">{{ data.type }}</Tag>
                    </template>
                </Column>
                <Column field="comment" :header="$t('extended.reputation.comment')" />
                <Column field="date" :header="$t('extended.reputation.date')" style="width: 180px" />

                <template #empty>
                    <div class="text-center py-8 text-muted-color">{{ $t('extended.reputation.no_logs') }}</div>
                </template>
            </DataTable>
        </div>
    </Fluid>
</template>
