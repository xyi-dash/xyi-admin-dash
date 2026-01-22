<template>
  <div class="dashboard">
<div class="header">
    <div class="header__left">
      <strong class="header__name">
        {{ profile?.account?.name || account?.name || '...' }}
      </strong>
      <span class="header__server">({{ account?.server }})</span>
      <span v-if="profile?.account?.is_online" class="status-online">[online]</span>
    </div>

    <div class="header__actions">
      <button class="btn btn--lang" @click="toggleLocale" :title="currentLocale === 'ru' ? 'Switch to English' : 'Переключить на русский'">
        {{ currentLocale.toUpperCase() }}
      </button>
      <button v-if="profile?.is_admin" class="btn btn--admin" @click="goToAdmin" title="Admin Panel">
        {{ $t('dashboard.admin_panel') }}
        <svg viewBox="0 0 24 24">
          <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5Zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5Z"/>
        </svg>
      </button>


      <button class="btn btn--logout" @click="logout" :title="$t('dashboard.logout')">
        <svg viewBox="0 0 24 24">
          <path d="M10 17l5-5-5-5v10Zm9-12h-8v2h8v10h-8v2h8c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2Z"/>
        </svg>
      </button>
    </div>
  </div>
</div>

<div v-if="loading" class="loading">{{ $t('common.loading') }}</div>
<div v-else-if="error" class="error">{{ error }}</div>
<template v-else-if="profile?.account">
      <div class="dash-card">
        <div class="dash-card__header">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
          <span>{{ $t('common.account') }}</span>
        </div>
        <div class="dash-card__body">
          <div class="dash-row">
            <span class="dash-row__label">{{ $t('player.nickname') }}</span>
            <span class="dash-row__value">{{ profile.account.name }}</span>
          </div>
          <div class="dash-row">
            <span class="dash-row__label">{{ $t('player.mail') }}</span>
            <span class="dash-row__value">{{ profile.account.email || '-' }}</span>
          </div>
          <div class="dash-row">
            <span class="dash-row__label">{{ $t('player.date_registered') }}</span>
            <span class="dash-row__value">{{ profile.account.registered_at }}</span>
          </div>
          <div class="dash-row">
            <span class="dash-row__label">{{ $t('player.reputation') }}</span>
            <span class="dash-row__value dash-row__value--green">+{{ profile.account.reputation || 5 }}</span>
          </div>
        </div>
      </div>

      <div class="dash-card">
        <div class="dash-card__header dash-card__header--yellow">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
          </svg>
          <span>{{ $t('player.stat') }}</span>
        </div>
        <div class="dash-card__body">
          <div class="dash-row">
            <span class="dash-row__label">{{ $t('player.kills') }}</span>
            <span class="dash-row__value dash-row__value--green">{{ profile.account.stats.kills }}</span>
          </div>
          <div class="dash-row">
            <span class="dash-row__label">{{ $t('player.deaths') }}</span>
            <span class="dash-row__value dash-row__value--red">{{ profile.account.stats.deaths }}</span>
          </div>
          <div class="dash-row">
            <span class="dash-row__label">{{ $t('player.kd_ratio') }}</span>
            <span class="dash-row__value">{{ calculateKD }}</span>
          </div>
        </div>
      </div>

      <div class="dash-card">
        <div class="dash-card__header dash-card__header--green">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
          </svg>
          <span>GangWar</span>
        </div>
        <div class="dash-card__body">
          <div class="gang-grid">
            <div class="gang-box gang-box--grove">
              <div class="gang-box__value">{{ profile.gangwar?.grove || 1688 }}</div>
              <div class="gang-box__label">Grove</div>
            </div>
            <div class="gang-box gang-box--ballas">
              <div class="gang-box__value">{{ profile.gangwar?.ballas || 3492 }}</div>
              <div class="gang-box__label">Ballas</div>
            </div>
            <div class="gang-box gang-box--vagos">
              <div class="gang-box__value">{{ profile.gangwar?.vagos || 1903 }}</div>
              <div class="gang-box__label">Vagos</div>
            </div>
            <div class="gang-box gang-box--aztec">
              <div class="gang-box__value">{{ profile.gangwar?.aztec || 5039 }}</div>
              <div class="gang-box__label">Aztec</div>
            </div>
          </div>
        </div>
      </div>

      <div class="dash-card">
        <div class="dash-card__header dash-card__header--red">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          <span>{{ $t('player.security') }}</span>
        </div>
        <div class="dash-card__body">
          <div class="dash-row">
            <span class="dash-row__label">Google Type:</span>
            <span class="dash-row__value">{{ profile.account.security.google_type || 1 }}</span>
          </div>

        </div>
      </div>

      <div class="dash-card">
        <div class="dash-card__header dash-card__header--orange">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="3"/>
            <path d="M12 1v6m0 6v6m11-7h-6m-6 0H1"/>
          </svg>
          <span>{{ $t('player.additional') }}</span>
        </div>
        <div class="dash-card__body">
          <div class="dash-row">
            <span class="dash-row__label">PREMIUM:</span>
            <span class="dash-row__value dash-row__value--muted">{{ profile.account.donate.has_premium ? $t('common.yes') : $t('common.no') }}</span>
          </div>
          <div class="dash-row">
            <span class="dash-row__label">ModeX2:</span>
            <span class="dash-row__value dash-row__value--green">{{ $t('common.yes') }}</span>
          </div>
        </div>
      </div>

      <div class="dash-card">
        <div class="dash-card__header dash-card__header--gold">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="1" x2="12" y2="23"/>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
          <span>{{ $t('player.donat') }}</span>
        </div>
        <div class="dash-card__body">
          <div class="dash-row">
            <span class="dash-row__label">{{ $t('player.balance') }}</span>
            <span class="dash-row__value dash-row__value--gold">{{ profile.account.donate.balance }}</span>
          </div>
          <div class="dash-row">
            <span class="dash-row__label">{{ $t('common.total') }}:</span>
            <span class="dash-row__value dash-row__value--gold">{{ profile.account.donate.total_donated }}</span>
          </div>
        </div>
      </div>
</template>
</template>

<script>
import axios from 'axios'
import { clearAdminSession } from '../router'
import { setLocale, getLocale } from '../i18n'

export default {
  name: 'Dashboard',
  data() {
    return {
      account: null,
      profile: null,
      loading: true,
      error: null,
      currentLocale: getLocale()
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
        this.error = this.$t('errors.failed_load')
      } finally {
        this.loading = false
      }
    },
    async goToAdmin() {
      try {
        const response = await axios.post('/api/admin/prepare-redirect')
        const token = response.data.token
        window.location.href = `https://admin.monser-dm.nl?token=${token}`
      } catch (err) {
        if (err.response?.status === 403) {
          alert(err.response.data?.message || 'You are not an admin')
        } else {
          alert('Failed to prepare admin redirect')
        }
      }
    },
    logout() {
      localStorage.removeItem('token')
      localStorage.removeItem('account')
      clearAdminSession()
      this.$router.push('/login')
    },
    toggleLocale() {
      const newLocale = this.currentLocale === 'ru' ? 'en' : 'ru'
      setLocale(newLocale)
      this.currentLocale = newLocale
    }
  }
}
</script>

<style scoped>
@import '../../css/account.scss';
@import '../../css/app.scss';
</style>
