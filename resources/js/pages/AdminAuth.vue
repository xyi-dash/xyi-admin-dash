<template>
  <div class="admin-auth">
    <h1>{{ $t('admin_auth.title') }}</h1>
    <p>{{ accountName }}</p>
    
    <div v-if="targetServer" class="server-notice">
      {{ $t('admin_auth.unlocking_server') }}: <strong>{{ serverName }}</strong>
    </div>
    
    <div v-if="error" class="error">{{ error }}</div>
    
    <form @submit.prevent="submit">
      <input
        v-model="password"
        type="password"
        :placeholder="$t('admin_auth.password_placeholder')"
        :disabled="loading"
        autofocus
      />
      <button type="submit" :disabled="loading">
        {{ loading ? '...' : $t('admin_auth.login') }}
      </button>
    </form>
    
    <button class="back" @click="$router.push('/dashboard')">← {{ $t('admin_auth.back') }}</button>
  </div>
</template>

<script>
import axios from 'axios'
import { updateUnlockedServers } from '../router'

export default {
  name: 'AdminAuth',
  data() {
    return {
      password: '',
      loading: false,
      error: null,
      targetServer: null
    }
  },
  computed: {
    accountName() {
      const acc = localStorage.getItem('account')
      if (acc) {
        try {
          return JSON.parse(acc).name
        } catch (e) {}
      }
      return 'reimu'
    },
    serverName() {
      const names = { one: 'Server 01', two: 'Server 02', three: 'Server 03' }
      return names[this.targetServer] || this.targetServer
    }
  },
  mounted() {
    this.targetServer = this.$route.query.server || null
  },
  methods: {
    async submit() {
      this.error = null
      this.loading = true
      
      try {
        const payload = { password: this.password }
        if (this.targetServer) {
          payload.server = this.targetServer
        }
        
        const res = await axios.post('/api/admin/auth', payload)
        
        updateUnlockedServers(res.data.unlocked_servers)
        
        const redirect = this.targetServer ? '/admin?server=' + this.targetServer : '/admin'
        this.$router.push(redirect)
      } catch (err) {
        if (err.response?.status === 401) {
          this.error = 'wrong spell card'
        } else if (err.response?.status === 403) {
          this.error = err.response.data?.message || this.$t('errors.not_admin')
        } else {
          this.error = this.$t('errors.failed_load')
        }
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.admin-auth {
  padding: 40px;
  color: #fff;
  max-width: 300px;
  margin: 0 auto;
}
h1 {
  font-size: 18px;
  margin-bottom: 8px;
}
p {
  opacity: 0.6;
  margin-bottom: 20px;
}
form {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
input {
  padding: 10px;
  background: #222;
  border: 1px solid #444;
  color: #fff;
  border-radius: 4px;
}
button {
  padding: 10px;
  background: #333;
  border: 1px solid #555;
  color: #fff;
  cursor: pointer;
  border-radius: 4px;
}
button:hover {
  background: #444;
}
.back {
  margin-top: 20px;
  background: transparent;
  border: none;
}
.error {
  color: #ef4444;
  margin-bottom: 10px;
}
.server-notice {
  background: #1a1a2e;
  border: 1px solid #444;
  padding: 10px;
  margin-bottom: 15px;
  border-radius: 4px;
}
</style>
