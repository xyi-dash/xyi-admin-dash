<template>
  <Dashboard v-if="account" :account="account" @logout="handleLogout" />
  <Auth v-else @login="handleLogin" />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Auth from './pages/Auth.vue'
import Dashboard from './pages/Dashboard.vue'

const account = ref(null)

onMounted(() => {
  const saved = localStorage.getItem('account')
  if (saved) {
    try {
      account.value = JSON.parse(saved)
    } catch (e) {
      localStorage.removeItem('account')
      localStorage.removeItem('token')
    }
  }
})

function handleLogin(acc) {
  account.value = acc
}

function handleLogout() {
  account.value = null
}
</script>
