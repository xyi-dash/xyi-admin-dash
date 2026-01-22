<script setup>
import api from '@/service/api'
import { useAuthStore } from '@/stores/auth'
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const player = ref(null)

import { useI18n } from 'vue-i18n'
const { t } = useI18n()

const getRankLabel = (rank) => t(`extended.player_stats.ranks.${rank}`)

onMounted(async () => {
    await loadPlayer()
})

async function loadPlayer() {
    loading.value = true
    try {
        const serverParam = authStore.currentServer ? `?server=${authStore.currentServer}` : ''
        const response = await api.get(`/admin/players/${route.params.id}${serverParam}`)
        player.value = response.data.data
    } catch (error) {
        // 境界の境目に消えた (vanished into the boundary's edge)
        console.warn('player stats yeeted themselves')
    } finally {
        loading.value = false
    }
}

function goBack() {
    router.push({ name: 'extended-players' })
}
</script>

<template>
    <div class="card">
        <div class="flex items-center gap-2 mb-4">
            <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
            <h5 class="m-0">{{ $t('extended.player_stats.title') }}</h5>
        </div>
        
        <ProgressSpinner v-if="loading" class="flex justify-center" />
        
        <div v-else-if="player" class="grid">
            <div class="col-12 lg:col-6">
                <div class="card">
                    <h6>{{ $t('extended.player_stats.basic_info') }}</h6>
                    <div class="grid">
                        <div class="col-6">
                            <div class="text-muted-color mb-1">{{ $t('extended.player_stats.account_id') }}</div>
                            <div class="font-semibold">{{ player.id }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">{{ $t('extended.player_stats.name') }}</div>
                            <div class="font-semibold">{{ player.name }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">{{ $t('extended.player_stats.email') }}</div>
                            <div class="font-semibold">
                                {{ player.email || '-' }}
                                <i v-if="player.email_verified" class="pi pi-check-circle text-green-500 ml-1"></i>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">{{ $t('extended.player_stats.rank') }}</div>
                            <Tag>{{ getRankLabel(player.rank) }}</Tag>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">{{ $t('extended.player_stats.registered') }}</div>
                            <div class="font-semibold">{{ player.registered_at }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">{{ $t('extended.player_stats.last_online') }}</div>
                            <div class="font-semibold">{{ player.last_online }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 lg:col-6">
                <div class="card">
                    <h6>{{ $t('extended.player_stats.connection_info') }}</h6>
                    <div class="grid">
                        <div class="col-6">
                            <div class="text-muted-color mb-1">{{ $t('extended.player_stats.last_ip') }}</div>
                            <div class="font-mono">{{ player.ip_last }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">{{ $t('extended.player_stats.reg_ip') }}</div>
                            <div class="font-mono">{{ player.ip_reg }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 lg:col-6">
                <div class="card">
                    <h6>{{ $t('extended.player_stats.statistics') }}</h6>
                    <div class="grid">
                        <div class="col-4">
                            <div class="text-muted-color mb-1">{{ $t('extended.player_stats.level') }}</div>
                            <div class="text-xl font-semibold">{{ player.level }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted-color mb-1">{{ $t('extended.player_stats.cash') }}</div>
                            <div class="text-xl font-semibold text-green-500">${{ player.cash?.toLocaleString() }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted-color mb-1">{{ $t('extended.player_stats.donate') }}</div>
                            <div class="text-xl font-semibold text-yellow-500">{{ player.donate?.money || 0 }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted-color mb-1">{{ $t('extended.player_stats.kills') }}</div>
                            <div class="font-semibold">{{ player.kills }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted-color mb-1">{{ $t('extended.player_stats.deaths') }}</div>
                            <div class="font-semibold">{{ player.deaths }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted-color mb-1">{{ $t('extended.player_stats.kd') }}</div>
                            <div class="font-semibold">{{ player.kd }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="player.gangwar" class="col-12 lg:col-6">
                <div class="card">
                    <h6>{{ $t('extended.player_stats.gangwar_stats') }}</h6>
                    <div class="grid">
                        <div class="col-6">
                            <div class="text-muted-color mb-1">Grove</div>
                            <div class="font-semibold text-green-500">{{ player.gangwar.grove }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">Ballas</div>
                            <div class="font-semibold text-purple-500">{{ player.gangwar.ballas }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">Vagos</div>
                            <div class="font-semibold text-yellow-500">{{ player.gangwar.vagos }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">Aztec</div>
                            <div class="font-semibold text-cyan-500">{{ player.gangwar.aztec }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div v-else class="text-center py-8">
            <i class="pi pi-exclamation-triangle text-4xl text-yellow-500 mb-4"></i>
            <p class="text-muted-color">{{ $t('extended.player_stats.player_not_found') }}</p>
        </div>
    </div>
</template>

