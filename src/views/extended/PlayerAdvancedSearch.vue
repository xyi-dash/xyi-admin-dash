<script setup>
import { ref, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useConfirm } from 'primevue/useconfirm';
import { useI18n } from 'vue-i18n';
import api from '@/service/api';

const router = useRouter();
const authStore = useAuthStore();
const confirm = useConfirm();
const { t } = useI18n();

const loading = ref(false);
const searched = ref(false);
const data = ref([]);
let abortController = null;

const defaultFilters = () => ({
    nickname: '',
    nickname_like: false,
    account_id: null,
    ip: '',
    ip_like: false,
    email: '',
    email_like: false,
    level_min: null,
    level_max: null,
    kills_min: null,
    kills_max: null,
    cash_min: null,
    cash_max: null,
    donate_min: null,
    donate_max: null
});

const filters = ref(defaultFilters());

function hasSlowFilters() {
    const f = filters.value;
    return (f.nickname && f.nickname_like) || (f.ip && f.ip_like) || (f.email && f.email_like);
}

function initiateSearch() {
    const f = filters.value;
    const hasFilters = f.nickname || f.account_id || f.ip || f.email ||
        f.level_min !== null || f.level_max !== null ||
        f.kills_min !== null || f.kills_max !== null ||
        f.cash_min !== null || f.cash_max !== null ||
        f.donate_min !== null || f.donate_max !== null;

    if (!hasFilters) return;

    if (hasSlowFilters()) {
        confirm.require({
            message: t('extended.player_search.slow_search_warning'),
            header: t('extended.player_search.slow_search_title'),
            icon: 'pi pi-exclamation-triangle',
            acceptLabel: t('extended.player_search.continue_search'),
            rejectLabel: t('common.cancel'),
            accept: () => executeSearch()
        });
    } else {
        executeSearch();
    }
}

async function executeSearch() {
    const f = filters.value;

    if (abortController) {
        abortController.abort();
    }
    abortController = new AbortController();

    loading.value = true;
    searched.value = false;

    try {
        const params = new URLSearchParams();
        if (f.nickname) params.append('nickname', f.nickname);
        if (f.nickname_like) params.append('nickname_like', '1');
        if (f.account_id) params.append('account_id', f.account_id);
        if (f.ip) params.append('ip', f.ip);
        if (f.ip_like) params.append('ip_like', '1');
        if (f.email) params.append('email', f.email);
        if (f.email_like) params.append('email_like', '1');
        if (f.level_min !== null) params.append('level_min', f.level_min);
        if (f.level_max !== null) params.append('level_max', f.level_max);
        if (f.kills_min !== null) params.append('kills_min', f.kills_min);
        if (f.kills_max !== null) params.append('kills_max', f.kills_max);
        if (f.cash_min !== null) params.append('cash_min', f.cash_min);
        if (f.cash_max !== null) params.append('cash_max', f.cash_max);
        if (f.donate_min !== null) params.append('donate_min', f.donate_min);
        if (f.donate_max !== null) params.append('donate_max', f.donate_max);
        if (authStore.currentServer) params.append('server', authStore.currentServer);

        const response = await api.get(`/admin/players/search/advanced?${params}`, {
            signal: abortController.signal
        });
        data.value = response.data.data || [];
        searched.value = true;
    } catch (e) {
        if (e.name !== 'CanceledError' && e.name !== 'AbortError') {
            console.warn('search went to buy cigarettes');
        }
    } finally {
        loading.value = false;
        abortController = null;
    }
}

function cancelSearch() {
    if (abortController) {
        abortController.abort();
        abortController = null;
    }
    loading.value = false;
}

function clearFilters() {
    cancelSearch();
    filters.value = defaultFilters();
    data.value = [];
    searched.value = false;
}

onBeforeUnmount(() => {
    if (abortController) {
        abortController.abort();
    }
});

const viewPlayer = (id) => router.push({ name: 'extended-player-stats', params: { id } });
const goBack = () => router.push({ name: 'extended-players' });
</script>

<template>
    <Fluid>
        <div class="card flex flex-col gap-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
                    <div class="font-semibold text-xl">{{ $t('extended.player_search.advanced_title') }}</div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex flex-col grow basis-0 gap-2">
                    <label for="nickname">{{ $t('extended.player_search.nickname') }}</label>
                    <InputText id="nickname" v-model="filters.nickname" :placeholder="$t('extended.player_search.nickname_placeholder')" @keyup.enter="initiateSearch" />
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="filters.nickname_like" :binary="true" inputId="nickLike" />
                        <label for="nickLike" class="cursor-pointer text-sm">{{ $t('extended.player_search.partial_match') }}</label>
                    </div>
                </div>
                <div class="flex flex-col grow basis-0 gap-2">
                    <label for="account_id">{{ $t('extended.player_search.account_id') }}</label>
                    <InputNumber id="account_id" v-model="filters.account_id" :placeholder="$t('extended.player_search.id_placeholder')" :useGrouping="false" @keyup.enter="initiateSearch" />
                </div>
                <div class="flex flex-col grow basis-0 gap-2">
                    <label for="ip">{{ $t('extended.player_search.ip') }}</label>
                    <InputText id="ip" v-model="filters.ip" :placeholder="$t('extended.player_search.ip_placeholder')" @keyup.enter="initiateSearch" />
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="filters.ip_like" :binary="true" inputId="ipLike" />
                        <label for="ipLike" class="cursor-pointer text-sm">{{ $t('extended.player_search.partial_match') }}</label>
                    </div>
                </div>
                <div class="flex flex-col grow basis-0 gap-2">
                    <label for="email">{{ $t('extended.player_search.email') }}</label>
                    <InputText id="email" v-model="filters.email" :placeholder="$t('extended.player_search.email_placeholder')" @keyup.enter="initiateSearch" />
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="filters.email_like" :binary="true" inputId="emailLike" />
                        <label for="emailLike" class="cursor-pointer text-sm">{{ $t('extended.player_search.partial_match') }}</label>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex flex-col grow basis-0 gap-2">
                    <label>{{ $t('extended.player_search.level_range') }}</label>
                    <div class="flex gap-2">
                        <InputNumber v-model="filters.level_min" :placeholder="$t('common.min')" :min="0" @keyup.enter="initiateSearch" />
                        <InputNumber v-model="filters.level_max" :placeholder="$t('common.max')" :min="0" @keyup.enter="initiateSearch" />
                    </div>
                </div>
                <div class="flex flex-col grow basis-0 gap-2">
                    <label>{{ $t('extended.player_search.kills_range') }}</label>
                    <div class="flex gap-2">
                        <InputNumber v-model="filters.kills_min" :placeholder="$t('common.min')" :min="0" @keyup.enter="initiateSearch" />
                        <InputNumber v-model="filters.kills_max" :placeholder="$t('common.max')" :min="0" @keyup.enter="initiateSearch" />
                    </div>
                </div>
                <div class="flex flex-col grow basis-0 gap-2">
                    <label>{{ $t('extended.player_search.cash_range') }}</label>
                    <div class="flex gap-2">
                        <InputNumber v-model="filters.cash_min" :placeholder="$t('common.min')" @keyup.enter="initiateSearch" />
                        <InputNumber v-model="filters.cash_max" :placeholder="$t('common.max')" @keyup.enter="initiateSearch" />
                    </div>
                </div>
                <div class="flex flex-col grow basis-0 gap-2">
                    <label>{{ $t('extended.player_search.donate_range') }}</label>
                    <div class="flex gap-2">
                        <InputNumber v-model="filters.donate_min" :placeholder="$t('common.min')" :min="0" @keyup.enter="initiateSearch" />
                        <InputNumber v-model="filters.donate_max" :placeholder="$t('common.max')" :min="0" @keyup.enter="initiateSearch" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <Button :label="$t('common.clear')" icon="pi pi-times" severity="secondary" outlined @click="clearFilters" />
                <Button v-if="loading" :label="$t('common.cancel')" icon="pi pi-ban" severity="danger" @click="cancelSearch" />
                <Button v-else :label="$t('common.search')" icon="pi pi-search" @click="initiateSearch" />
            </div>
        </div>

        <ConfirmDialog />

        <div class="card flex flex-col gap-4">
            <DataTable :value="data" :loading="loading" stripedRows class="p-datatable-sm">
                <Column field="id" :header="$t('extended.player_search.id')" style="width: 100px">
                    <template #body="{ data }">
                        <span class="font-mono">{{ data.id }}</span>
                    </template>
                </Column>
                <Column field="name" :header="$t('extended.player_search.name')">
                    <template #body="{ data }">
                        <span class="font-semibold">{{ data.name }}</span>
                    </template>
                </Column>
                <Column field="level" :header="$t('extended.player_search.level')" style="width: 80px" />
                <Column field="kills" :header="$t('extended.player_search.kills')" style="width: 100px">
                    <template #body="{ data }">{{ data.kills?.toLocaleString() }}</template>
                </Column>
                <Column field="deaths" :header="$t('extended.player_search.deaths')" style="width: 100px">
                    <template #body="{ data }">{{ data.deaths?.toLocaleString() }}</template>
                </Column>
                <Column field="cash" :header="$t('extended.player_stats.cash')" style="width: 120px">
                    <template #body="{ data }">
                        <span class="text-green-500">${{ data.cash?.toLocaleString() }}</span>
                    </template>
                </Column>
                <Column field="donate_money" :header="$t('extended.player_search.donate')" style="width: 100px">
                    <template #body="{ data }">
                        <span class="text-yellow-500">{{ data.donate_money || 0 }} ₽</span>
                    </template>
                </Column>
                <Column field="ip_last" :header="$t('extended.player_stats.last_ip')" style="width: 140px">
                    <template #body="{ data }">
                        <span class="font-mono text-sm">{{ data.ip_last || '-' }}</span>
                    </template>
                </Column>
                <Column :header="$t('common.actions')" style="width: 80px">
                    <template #body="{ data }">
                        <Button icon="pi pi-external-link" size="small" text rounded @click="viewPlayer(data.id)" />
                    </template>
                </Column>

                <template #empty>
                    <div class="text-center py-8 text-muted-color">
                        {{ searched ? $t('extended.player_search.no_players') : $t('extended.player_search.enter_filters') }}
                    </div>
                </template>
            </DataTable>
        </div>
    </Fluid>
</template>
