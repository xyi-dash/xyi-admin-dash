<template>
  <div class="admin-auth">
    <h1>Авторизация в админку</h1>
    <p>{{ accountName }}</p>
    
    <div v-if="error" class="error">{{ error }}</div>
    
    <form @submit.prevent="submit">
      <input
        v-model="password"
        type="password"
        placeholder="Админ пароль"
        :disabled="loading"
        autofocus
      />
      <button type="submit" :disabled="loading">
        {{ loading ? '...' : 'Войти' }}
      </button>
    </form>
    
    <button class="back" @click="$router.push('/dashboard')">← назад</button>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AdminAuth',
  data() {
    return {
      password: '',
      loading: false,
      error: null
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
      return '???'
    }
  },
  methods: {
    async submit() {
      this.error = null
      this.loading = true
      
      try {
        await axios.post('/api/admin/auth', {
          password: this.password
        })
        
        localStorage.setItem('admin_session', 'true')
        this.$router.push('/admin')
      } catch (err) {
        if (err.response?.status === 401) {
          this.error = 'wrong password'
        } else if (err.response?.status === 403) {
          this.error = 'youre not an admin'
        } else {
          this.error = 'error'
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
</style>

