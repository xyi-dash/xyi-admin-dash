<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/service/api'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const player = ref(null)

const rankLabels = {
    0: 'Player',
    1: 'VIP',
    2: 'Premium',
    3: 'Legend'
}

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
            <h5 class="m-0">Player Stats</h5>
        </div>
        
        <ProgressSpinner v-if="loading" class="flex justify-center" />
        
        <div v-else-if="player" class="grid">
            <div class="col-12 lg:col-6">
                <div class="card">
                    <h6>Basic Info</h6>
                    <div class="grid">
                        <div class="col-6">
                            <div class="text-muted-color mb-1">Account ID</div>
                            <div class="font-semibold">{{ player.id }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">Name</div>
                            <div class="font-semibold">{{ player.name }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">Email</div>
                            <div class="font-semibold">
                                {{ player.email || '-' }}
                                <i v-if="player.email_verified" class="pi pi-check-circle text-green-500 ml-1"></i>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">Rank</div>
                            <Tag>{{ rankLabels[player.rank] || 'Unknown' }}</Tag>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">Registered</div>
                            <div class="font-semibold">{{ player.registered_at }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">Last Online</div>
                            <div class="font-semibold">{{ player.last_online }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 lg:col-6">
                <div class="card">
                    <h6>Connection Info</h6>
                    <div class="grid">
                        <div class="col-6">
                            <div class="text-muted-color mb-1">Last IP</div>
                            <div class="font-mono">{{ player.ip_last }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted-color mb-1">Registration IP</div>
                            <div class="font-mono">{{ player.ip_reg }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 lg:col-6">
                <div class="card">
                    <h6>Statistics</h6>
                    <div class="grid">
                        <div class="col-4">
                            <div class="text-muted-color mb-1">Level</div>
                            <div class="text-xl font-semibold">{{ player.level }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted-color mb-1">Cash</div>
                            <div class="text-xl font-semibold text-green-500">${{ player.cash?.toLocaleString() }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted-color mb-1">Donate</div>
                            <div class="text-xl font-semibold text-yellow-500">{{ player.donate?.money || 0 }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted-color mb-1">Kills</div>
                            <div class="font-semibold">{{ player.kills }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted-color mb-1">Deaths</div>
                            <div class="font-semibold">{{ player.deaths }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted-color mb-1">K/D</div>
                            <div class="font-semibold">{{ player.kd }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="player.gangwar" class="col-12 lg:col-6">
                <div class="card">
                    <h6>Gangwar Stats</h6>
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
            <p class="text-muted-color">Player not found</p>
        </div>
    </div>
</template>

