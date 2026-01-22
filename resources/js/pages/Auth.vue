<template>
  <div class="auth-wrapper">
    <div class="auth-container">
      <button class="lang-switcher" @click="toggleLocale" :title="currentLocale === 'ru' ? 'Switch to English' : 'Переключить на русский'">
        {{ currentLocale.toUpperCase() }}
      </button>
      <div class="header">
        <h1 class="logo">Monser DM</h1>
        <p class="subtitle">{{ $t('auth.title') }}</p>
      </div>

      <div class="steps">
        <div class="step active">1</div>
        <div class="step-line"></div>
        <div class="step inactive">2</div>
      </div>

      <div v-if="error" class="error-message">{{ error }}</div>
      <div v-if="success" class="success-message">{{ success }}</div>

      <form @submit.prevent="submitForm">
        <div class="form-group">
          <label class="form-label">{{ $t('auth.server') }}</label>
          <select v-model="form.server" class="form-input" required :disabled="loading">
            <option disabled value="">{{ $t('auth.select_server') }}</option>
            <option value="one">Server 01</option>
            <option value="two">Server 02</option>
            <option value="three">Server 03</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">{{ $t('auth.nickname') }}</label>
          <input
            v-model="form.nickname"
            class="form-input"
            :placeholder="$t('auth.nickname_placeholder')"
            required
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label class="form-label">{{ $t('auth.password') }}</label>
          <input
            v-model="form.password"
            type="password"
            class="form-input"
            :placeholder="$t('auth.password_placeholder')"
            required
            :disabled="loading"
          />
        </div>

        <button class="submit-btn" :disabled="loading">
          {{ loading ? $t('auth.logging_in') : $t('auth.login') }}
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { setLocale, getLocale } from '../i18n'

export default {
  name: 'Auth',
  data() {
    return {
      form: {
        server: '',
        nickname: '',
        password: ''
      },
      loading: false,
      error: null,
      success: null,
      currentLocale: getLocale()
    }
  },
  methods: {
    toggleLocale() {
      const newLocale = this.currentLocale === 'ru' ? 'en' : 'ru'
      setLocale(newLocale)
      this.currentLocale = newLocale
    },
    async submitForm() {
      this.error = null
      this.success = null
      this.loading = true

      try {
        const response = await axios.post('/api/auth/login', this.form)
        
        localStorage.setItem('token', response.data.token)
        localStorage.setItem('account', JSON.stringify(response.data.account))
        
        this.$router.push('/dashboard')
      } catch (err) {
        if (err.response?.status === 422) {
          const errors = err.response.data.errors
          this.error = Object.values(errors).flat()[0] || this.$t('errors.not_found')
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
.lang-switcher {
  position: absolute;
  top: 16px;
  right: 16px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #fff;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.lang-switcher:hover {
  background: rgba(255, 255, 255, 0.2);
}

.error-message {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #ef4444;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 16px;
  font-size: 14px;
}

.success-message {
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.3);
  color: #22c55e;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 16px;
  font-size: 14px;
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
