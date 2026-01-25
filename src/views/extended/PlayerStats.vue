<script setup>
import api from '@/service/api';
import { useAuthStore } from '@/stores/auth';
import { onMounted, ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { t } = useI18n();

const loading = ref(true);
const player = ref(null);

const getRankLabel = (rank) => t(`extended.player_stats.ranks.${rank}`);

const clanRanks = ['Рекрут', 'Участник', 'Старший', 'Заместитель', 'Офицер', 'Со-лидер', 'Лидер'];
const clanRankLabel = computed(() => {
    const rank = player.value?.clan?.rank;
    if (rank === undefined || rank === null) return '-';
    return clanRanks[rank] || `Ранг ${rank}`;
});

onMounted(() => loadPlayer());

async function loadPlayer() {
    loading.value = true;
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : '';
        const response = await api.get(`/admin/players/${route.params.id}${serverParam}`);
        player.value = response.data.data;
    } catch {
        console.warn('player evaporated');
    } finally {
        loading.value = false;
    }
}

const goBack = () => router.push({ name: 'extended-players' });
</script>

<template>
    <div class="flex flex-col gap-8">
        <div class="card">
            <div class="flex items-center gap-2 mb-4">
                <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
                <div class="font-semibold text-xl">{{ $t('extended.player_stats.title') }}</div>
            </div>

            <ProgressSpinner v-if="loading" class="flex justify-center py-8" />

            <template v-else-if="player">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <div class="text-surface-900 dark:text-surface-0 font-medium text-2xl mb-2">{{ player.name }}</div>
                        <div class="flex flex-wrap gap-4 text-sm text-muted-color">
                            <span>ID: <span class="font-mono text-surface-900 dark:text-surface-0">{{ player.id }}</span></span>
                            <span>{{ $t('extended.player_stats.registered') }}: <span class="text-surface-900 dark:text-surface-0">{{ player.registered_at }}</span></span>
                            <span>{{ $t('extended.player_stats.last_online') }}: <span class="text-surface-900 dark:text-surface-0">{{ player.last_online }}</span></span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Tag severity="info">{{ getRankLabel(player.rank) }}</Tag>
                        <Tag v-if="player.vip" severity="warn">VIP</Tag>
                        <Tag v-if="player.premium" severity="success">PREMIUM</Tag>
                    </div>
                </div>
            </template>

            <div v-else class="text-center py-8">
                <i class="pi pi-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
                <p class="text-muted-color">{{ $t('extended.player_stats.player_not_found') }}</p>
            </div>
        </div>

        <template v-if="player && !loading">
            <div class="grid grid-cols-12 gap-8">
                <div class="col-span-12 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">{{ $t('extended.player_stats.level') }}</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ player.level }}</div>
                            </div>
                            <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                                <i class="pi pi-star text-blue-500 text-xl!"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-12 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">{{ $t('extended.player_stats.cash') }}</span>
                                <div class="text-green-500 font-medium text-xl">${{ player.cash?.toLocaleString() }}</div>
                            </div>
                            <div class="flex items-center justify-center bg-green-100 dark:bg-green-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                                <i class="pi pi-dollar text-green-500 text-xl!"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-12 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">{{ $t('extended.player_stats.donate') }}</span>
                                <div class="text-yellow-500 font-medium text-xl">{{ player.donate?.money || 0 }} ₽</div>
                            </div>
                            <div class="flex items-center justify-center bg-yellow-100 dark:bg-yellow-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                                <i class="pi pi-credit-card text-yellow-500 text-xl!"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-12 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">{{ $t('extended.player_stats.kd') }}</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ player.kd }}</div>
                            </div>
                            <div class="flex items-center justify-center bg-red-100 dark:bg-red-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                                <i class="pi pi-chart-line text-red-500 text-xl!"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-8">
                <div class="col-span-12 xl:col-span-6">
                    <div class="card">
                        <div class="font-semibold text-xl mb-4">{{ $t('extended.player_stats.statistics') }}</div>
                        <ul class="list-none p-0 m-0">
                            <li class="flex items-center py-4 border-b border-surface-200 dark:border-surface-700">
                                <div class="text-muted-color w-1/2">{{ $t('extended.player_stats.kills') }}</div>
                                <div class="text-surface-900 dark:text-surface-0 font-medium w-1/2">{{ player.kills?.toLocaleString() }}</div>
                            </li>
                            <li class="flex items-center py-4 border-b border-surface-200 dark:border-surface-700">
                                <div class="text-muted-color w-1/2">{{ $t('extended.player_stats.deaths') }}</div>
                                <div class="text-surface-900 dark:text-surface-0 font-medium w-1/2">{{ player.deaths?.toLocaleString() }}</div>
                            </li>
                            <li class="flex items-center py-4 border-b border-surface-200 dark:border-surface-700">
                                <div class="text-muted-color w-1/2">{{ $t('extended.player_stats.reputation') }}</div>
                                <div class="font-medium w-1/2" :class="{ 'text-green-500': player.reputation > 0, 'text-red-500': player.reputation < 0 }">{{ player.reputation || 0 }}</div>
                            </li>
                            <li class="flex items-center py-4">
                                <div class="text-muted-color w-1/2">{{ $t('extended.player_stats.playtime') }}</div>
                                <div class="text-surface-900 dark:text-surface-0 font-medium w-1/2">{{ player.playtime || '0ч' }}</div>
                            </li>
                        </ul>
                    </div>

                    <div v-if="player.gangwar" class="card">
                        <div class="font-semibold text-xl mb-4">{{ $t('extended.player_stats.gangwar_stats') }}</div>
                        <ul class="list-none p-0 m-0">
                            <li class="flex items-center py-4 border-b border-surface-200 dark:border-surface-700">
                                <div class="text-muted-color w-1/2">Grove Street</div>
                                <div class="text-green-500 font-medium w-1/2">{{ player.gangwar.grove?.toLocaleString() }}</div>
                            </li>
                            <li class="flex items-center py-4 border-b border-surface-200 dark:border-surface-700">
                                <div class="text-muted-color w-1/2">Ballas</div>
                                <div class="text-purple-500 font-medium w-1/2">{{ player.gangwar.ballas?.toLocaleString() }}</div>
                            </li>
                            <li class="flex items-center py-4 border-b border-surface-200 dark:border-surface-700">
                                <div class="text-muted-color w-1/2">Vagos</div>
                                <div class="text-yellow-500 font-medium w-1/2">{{ player.gangwar.vagos?.toLocaleString() }}</div>
                            </li>
                            <li class="flex items-center py-4">
                                <div class="text-muted-color w-1/2">Aztecas</div>
                                <div class="text-cyan-500 font-medium w-1/2">{{ player.gangwar.aztec?.toLocaleString() }}</div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-span-12 xl:col-span-6">
                    <div class="card">
                        <div class="font-semibold text-xl mb-4">{{ $t('extended.player_stats.basic_info') }}</div>
                        <ul class="list-none p-0 m-0">
                            <li class="flex items-center py-4 border-b border-surface-200 dark:border-surface-700">
                                <div class="text-muted-color w-1/2">{{ $t('extended.player_stats.email') }}</div>
                                <div class="text-surface-900 dark:text-surface-0 font-medium w-1/2 flex items-center gap-2">
                                    {{ player.email || '-' }}
                                    <i v-if="player.email_verified" class="pi pi-check-circle text-green-500"></i>
                                </div>
                            </li>
                            <li class="flex items-center py-4 border-b border-surface-200 dark:border-surface-700">
                                <div class="text-muted-color w-1/2">{{ $t('extended.player_stats.last_ip') }}</div>
                                <div class="text-surface-900 dark:text-surface-0 font-mono w-1/2">{{ player.ip_last || '-' }}</div>
                            </li>
                            <li class="flex items-center py-4 border-b border-surface-200 dark:border-surface-700">
                                <div class="text-muted-color w-1/2">{{ $t('extended.player_stats.reg_ip') }}</div>
                                <div class="text-surface-900 dark:text-surface-0 font-mono w-1/2">{{ player.ip_reg || '-' }}</div>
                            </li>
                            <li class="flex items-center py-4 border-b border-surface-200 dark:border-surface-700">
                                <div class="text-muted-color w-1/2">{{ $t('extended.player_stats.td_pass') }}</div>
                                <div class="w-1/2">
                                    <Tag :severity="player.security?.td_pass_set ? 'success' : 'danger'" size="small">
                                        {{ player.security?.td_pass_set ? $t('common.set') : $t('common.not_set') }}
                                    </Tag>
                                </div>
                            </li>
                            <li class="flex items-center py-4">
                                <div class="text-muted-color w-1/2">{{ $t('extended.player_stats.vid_kod') }}</div>
                                <div class="text-surface-900 dark:text-surface-0 font-medium w-1/2">{{ player.security?.vid_kod === 0 ? $t('extended.player_stats.vid_kod_every') : $t('extended.player_stats.vid_kod_ip') }}</div>
                            </li>
                        </ul>
                    </div>

                    <div v-if="player.clan?.id" class="card">
                        <div class="font-semibold text-xl mb-4">{{ $t('extended.player_stats.clan_info') }}</div>
                        <ul class="list-none p-0 m-0">
                            <li class="flex items-center py-4 border-b border-surface-200 dark:border-surface-700">
                                <div class="text-muted-color w-1/2">{{ $t('extended.player_stats.clan_name') }}</div>
                                <div class="text-surface-900 dark:text-surface-0 font-semibold w-1/2">{{ player.clan.name }}</div>
                            </li>
                            <li class="flex items-center py-4 border-b border-surface-200 dark:border-surface-700">
                                <div class="text-muted-color w-1/2">{{ $t('extended.player_stats.clan_rank') }}</div>
                                <div class="text-surface-900 dark:text-surface-0 font-medium w-1/2">{{ clanRankLabel }}</div>
                            </li>
                            <li class="flex items-center py-4">
                                <div class="text-muted-color w-1/2">{{ $t('extended.player_stats.clan_rep') }}</div>
                                <div class="text-surface-900 dark:text-surface-0 font-medium w-1/2">{{ player.clan.rep?.toLocaleString() }}</div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div v-if="player.matchmaking" class="card">
                <div class="font-semibold text-xl mb-4">{{ $t('extended.player_stats.mm_stats') }}</div>
                <div class="grid grid-cols-12 gap-8">
                    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">ELO</span>
                                <div class="font-medium text-xl" :class="{ 'text-green-500': player.matchmaking.elo >= 1200, 'text-yellow-500': player.matchmaking.elo >= 1000 && player.matchmaking.elo < 1200, 'text-red-500': player.matchmaking.elo < 1000 }">{{ player.matchmaking.elo }}</div>
                            </div>
                            <div class="flex items-center justify-center bg-purple-100 dark:bg-purple-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                                <i class="pi pi-bolt text-purple-500 text-xl!"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">{{ $t('extended.matchmaking.games') }}</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ player.matchmaking.games }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">{{ $t('extended.matchmaking.wins') }}</span>
                                <div class="text-green-500 font-medium text-xl">{{ player.matchmaking.wins }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-12 lg:col-span-6 xl:col-span-3">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">{{ $t('extended.matchmaking.winrate') }}</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">{{ player.matchmaking.winrate }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
                <ul class="list-none p-0 m-0 mt-4">
                    <li class="flex items-center py-4 border-b border-surface-200 dark:border-surface-700">
                        <div class="text-muted-color w-1/4">{{ $t('extended.matchmaking.kills') }}</div>
                        <div class="text-surface-900 dark:text-surface-0 font-medium w-1/4">{{ player.matchmaking.kills }}</div>
                        <div class="text-muted-color w-1/4">{{ $t('extended.matchmaking.deaths') }}</div>
                        <div class="text-surface-900 dark:text-surface-0 font-medium w-1/4">{{ player.matchmaking.deaths }}</div>
                    </li>
                    <li class="flex items-center py-4">
                        <div class="text-muted-color w-1/4">{{ $t('extended.matchmaking.mvp') }}</div>
                        <div class="text-yellow-500 font-medium w-1/4">{{ player.matchmaking.mvp }}</div>
                        <div class="text-muted-color w-1/4">{{ $t('extended.player_stats.mm_time') }}</div>
                        <div class="text-surface-900 dark:text-surface-0 font-medium w-1/4">{{ player.matchmaking.game_time || '0ч' }}</div>
                    </li>
                </ul>
            </div>

            <div v-if="player.score_chase" class="card">
                <div class="font-semibold text-xl mb-4">{{ $t('extended.player_stats.copchase_stats') }}</div>
                <div class="flex justify-between">
                    <div>
                        <span class="block text-muted-color font-medium mb-4">{{ $t('extended.player_stats.score_chase') }}</span>
                        <div class="text-blue-500 font-medium text-2xl">{{ player.score_chase?.toLocaleString() }}</div>
                    </div>
                    <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-border" style="width: 2.5rem; height: 2.5rem">
                        <i class="pi pi-car text-blue-500 text-xl!"></i>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
