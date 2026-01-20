<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { redirectToDashboard } from '@/service/api'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const password = ref('')
const loading = ref(false)
const error = ref('')

const targetServer = computed(() => route.query.server || authStore.currentServer || authStore.user?.server)

const serverLabel = computed(() => {
    const labels = { one: 'Server One', two: 'Server Two', three: 'Server Three' }
    return labels[targetServer.value] || targetServer.value
})

const isAdditionalServer = computed(() => {
    return route.query.server && route.query.server !== authStore.currentServer
})

onMounted(() => {
    if (authStore.hasUnlockedServers && !route.query.server) router.push('/')
})

async function handleUnlock() {
    if (!password.value) {
        error.value = 'enter your admin password'
        return
    }
    
    loading.value = true
    error.value = ''
    
    const result = await authStore.unlockServer(password.value, targetServer.value)
    
    loading.value = false
    
    if (result.success) {
        if (isAdditionalServer.value) authStore.switchServer(targetServer.value)
        router.push('/')
    } else {
        error.value = result.message || 'invalid password'
    }
}

function goBack() {
    if (authStore.hasUnlockedServers) {
        router.push('/')
    } else {
        redirectToDashboard()
    }
}
</script>

<template>
    <div class="bg-surface-50 dark:bg-surface-950 flex items-center justify-center min-h-screen min-w-[100vw] overflow-hidden">
        <div class="flex flex-col items-center justify-center">
            <div style="border-radius: 56px; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)">
                <div class="w-full bg-surface-0 dark:bg-surface-900 py-20 px-8 sm:px-20" style="border-radius: 53px">
                    <div class="text-center mb-8">
                        <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-4">
                            {{ isAdditionalServer ? 'Unlock Server' : 'Admin Panel' }}
                        </div>
                        <span class="text-muted-color font-medium">
                            {{ isAdditionalServer ? `Enter admin password for ${serverLabel}` : 'Enter your admin password to continue' }}
                        </span>
                    </div>

                    <div v-if="authStore.user" class="mb-6 text-center">
                        <span class="text-surface-600 dark:text-surface-400">Logged in as </span>
                        <span class="text-primary font-semibold">{{ authStore.user.name }}</span>
                        <div v-if="targetServer" class="mt-2">
                            <Tag severity="info">{{ serverLabel }}</Tag>
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-surface-900 dark:text-surface-0 text-xl font-medium mb-2">Admin Password</label>
                        <Password 
                            id="password" 
                            v-model="password" 
                            placeholder="your admin password"
                            :toggleMask="true" 
                            class="w-full mb-4" 
                            inputClass="w-full"
                            :feedback="false"
                            @keyup.enter="handleUnlock"
                        />

                        <Message v-if="error" severity="error" class="mb-4">{{ error }}</Message>

                        <Button 
                            label="Unlock" 
                            class="w-full" 
                            :loading="loading"
                            @click="handleUnlock"
                        />
                        
                        <Button 
                            :label="authStore.hasUnlockedServers ? 'Cancel' : 'Back to Dashboard'" 
                            class="w-full mt-4" 
                            severity="secondary"
                            text
                            @click="goBack"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
