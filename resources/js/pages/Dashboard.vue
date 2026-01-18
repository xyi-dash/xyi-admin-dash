<template>
  <div class="dashboard">
    <div class="header">
      <strong>{{ profile?.account?.name || account?.name || '...' }}</strong>
      <span>({{ account?.server }})</span>
      <span v-if="profile?.account?.is_online">[online]</span>
      <button v-if="profile?.is_admin" @click="goToAdmin">админ-панель</button>
      <button @click="logout">I am dick and wang!</button>
    </div>

    <div v-if="loading">загрузка...</div>
    <div v-else-if="error">{{ error }}</div>
    
    <pre v-else-if="profile">{{ JSON.stringify(profile, null, 2) }}</pre>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'Dashboard',
  data() {
    return {
      account: null,
      profile: null,
      loading: true,
      error: null
    }
  },
  created() {
    const saved = localStorage.getItem('account')
    if (saved) {
      try {
        this.account = JSON.parse(saved)
      } catch (e) {}
    }
  },
  mounted() {
    this.loadProfile()
  },
  methods: {
    async loadProfile() {
      try {
        const response = await axios.get('/api/account/profile')
        this.profile = response.data
      } catch (err) {
        this.error = 'не удалось загрузить профиль'
      } finally {
        this.loading = false
      }
    },
    goToAdmin() {
      this.$router.push('/admin/login')
    },
    logout() {
      localStorage.removeItem('token')
      localStorage.removeItem('account')
      localStorage.removeItem('admin_session')
      this.$router.push('/login')
    }
  }
}
</script>

<style scoped>
.dashboard {
  padding: 20px;
  color: #fff;
}
.header {
  margin-bottom: 20px;
}
.header button {
  margin-left: 10px;
}
pre {
  background: rgba(255,255,255,0.1);
  padding: 16px;
  border-radius: 4px;
  overflow: auto;
  font-size: 12px;
}
</style>
