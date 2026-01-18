<template>
  <div class="admin-panel">
    <div v-if="loading">загрузка...</div>
    <div v-else-if="error">{{ error }} <button @click="goBack">назад</button></div>
    
    <div v-else>
      <h1>Панель Администратора</h1>
      <p>{{ myAdmin.name }} | Уровень {{ myAdmin.level }}{{ myAdmin.is_ga ? ' (GA)' : '' }}</p>
      
      <nav>
        <button @click="currentPage = 'home'" :class="{ active: currentPage === 'home' }">Главная</button>
        <button @click="currentPage = 'admins'" :class="{ active: currentPage === 'admins' }">Список админов</button>
        
        <!-- 6+ -->
        <template v-if="canViewLogs">
          <button @click="currentPage = 'actions'" :class="{ active: currentPage === 'actions' }">Действия администрации</button>
          <button @click="currentPage = 'warnings'" :class="{ active: currentPage === 'warnings' }">Выданные преды</button>
          <button @click="currentPage = 'purchases'" :class="{ active: currentPage === 'purchases' }">Мониторинг покупок</button>
        </template>
        
        <!-- 7lvls+ -->
        <template v-if="canViewRemoved">
          <button @click="currentPage = 'removed'" :class="{ active: currentPage === 'removed' }">Снятые админы</button>
        </template>
        
        <!-- 8lvls+ -->
        <template v-if="canViewGAActions">
          <button @click="currentPage = 'ga-actions'" :class="{ active: currentPage === 'ga-actions' }">Действия Гл. администрации</button>
        </template>
        
        <!-- 8+ only -->
        <template v-if="canManageServers">
          <button @click="currentPage = 'news'" :class="{ active: currentPage === 'news' }">Управление новостями</button>
          <button @click="currentPage = 'servers'" :class="{ active: currentPage === 'servers' }">Управление серверами</button>
        </template>
        
        <button @click="goBack">← выйти</button>
      </nav>

      <!-- home page -->
      <div v-if="currentPage === 'home'" class="page">
        <h2>Моя информация</h2>
        <table>
          <tr><td>Уровень:</td><td>{{ myAdmin.level }}</td></tr>
          <tr><td>GA статус:</td><td>{{ myAdmin.is_ga ? 'Да' : 'Нет' }}</td></tr>
          <tr><td>Предупреждений:</td><td>{{ myAdmin.warnings }}/3</td></tr>
          <tr><td>Кем назначен:</td><td>{{ myAdmin.appointed_by }}</td></tr>
          <tr><td>Дата назначения:</td><td>{{ myAdmin.appointed_date }}</td></tr>
          <tr><td>Последний визит:</td><td>{{ myAdmin.last_online }}</td></tr>
          <tr><td>Подтверждён:</td><td>{{ myAdmin.is_confirmed ? 'Да' : 'Нет' }}</td></tr>
          <tr><td>Репутация:</td><td>+{{ myAdmin.reputation.up }} / -{{ myAdmin.reputation.down }}</td></tr>
        </table>

        <h3>Онлайн</h3>
        <table>
          <tr><td>Сегодня:</td><td>{{ myAdmin.playtime.today }}</td></tr>
          <tr><td>Вчера:</td><td>{{ myAdmin.playtime.yesterday }}</td></tr>
          <tr><td>Позавчера:</td><td>{{ myAdmin.playtime.day_before }}</td></tr>
          <tr><td>За неделю:</td><td>{{ myAdmin.playtime.week }}</td></tr>
        </table>

        <!-- stats for 1-4 lvl -->
        <template v-if="myAdmin.stats">
          <h3>Статистика</h3>
          <table>
            <tr><td>Отыграно часов:</td><td>{{ myAdmin.stats.hours_played }}</td></tr>
            <tr><td>Выдано наказаний:</td><td>{{ myAdmin.stats.punishments_given }}</td></tr>
            <tr><td>Ответов на репорт:</td><td>{{ myAdmin.stats.reports_answered }}</td></tr>
          </table>
        </template>

        <!-- shop for 1-5 lvl -->
        <template v-if="canBuy">
          <h3>Магазин</h3>
          <p v-if="myAdmin.level < 5">Повышение: доступно</p>
          <p>Снять предупреждение: {{ myAdmin.warnings > 0 ? 'доступно' : 'нет предов' }}</p>
          <p><i>(платежка не реализована)</i></p>
        </template>
      </div>

      <!-- admins list page -->
      <div v-if="currentPage === 'admins'" class="page">
        <h2>Список администрации</h2>
        <p>Всего: {{ adminList?.total || 0 }} | Онлайн: {{ adminList?.online || 0 }}</p>
        
        <div v-for="level in [6,5,4,3,2,1]" :key="level">
          <h3>{{ level }} уровень</h3>
          <table v-if="getAdminsByLevel(level).length">
            <tr>
              <th>Админ</th>
              <th>Преды</th>
              <th>Репутация</th>
              <th>3 дня</th>
              <th>Неделя</th>
              <th>Статус</th>
            </tr>
            <tr v-for="admin in getAdminsByLevel(level)" :key="admin.id">
              <td>{{ admin.name }}</td>
              <td>{{ admin.warnings }}/3</td>
              <td>+{{ admin.reputation.up }}/-{{ admin.reputation.down }}</td>
              <td>{{ admin.playtime_3days }}</td>
              <td>{{ admin.playtime_week }}</td>
              <td>{{ admin.is_online ? '🟢' : '⚫' }}</td>
            </tr>
          </table>
          <p v-else><i>нет админов</i></p>
        </div>
      </div>

      <!-- placeholder pages -->
      <div v-if="currentPage === 'actions'" class="page">
        <h2>Действия администрации</h2>
        <p><i>не реализовано</i></p>
      </div>

      <div v-if="currentPage === 'ga-actions'" class="page">
        <h2>Действия Гл. администрации</h2>
        <p><i>не реализовано</i></p>
      </div>

      <div v-if="currentPage === 'warnings'" class="page">
        <h2>Выданные предупреждения</h2>
        <p><i>не реализовано</i></p>
      </div>

      <div v-if="currentPage === 'purchases'" class="page">
        <h2>Мониторинг покупок</h2>
        <p><i>не реализовано</i></p>
      </div>

      <div v-if="currentPage === 'removed'" class="page">
        <h2>Снятые администраторы</h2>
        <p><i>не реализовано</i></p>
      </div>

      <div v-if="currentPage === 'news'" class="page">
        <h2>Управление новостями</h2>
        <p><i>не реализовано</i></p>
      </div>

      <div v-if="currentPage === 'servers'" class="page">
        <h2>Управление серверами</h2>
        <p><i>не реализовано</i></p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AdminPanel',
  data() {
    return {
      myAdmin: null,
      adminList: null,
      loading: true,
      error: null,
      currentPage: 'home'
    }
  },
  computed: {
    // 6+: admin actions, warns, purchases
    canViewLogs() {
      if (!this.myAdmin) return false
      return this.myAdmin.level >= 7 || (this.myAdmin.level === 6 && this.myAdmin.is_ga)
    },
    // 7lvls: removed admins list
    canViewRemoved() {
      if (!this.myAdmin) return false
      return this.myAdmin.level >= 7
    },
    // 8lvl: ga actions
    canViewGAActions() {
      if (!this.myAdmin) return false
      return this.myAdmin.level >= 8
    },
    // 8+: news/servers management
    canManageServers() {
      if (!this.myAdmin) return false
      return this.myAdmin.level === 8 && this.myAdmin.is_ga
    },
    // pechenki 1-5: can buy promotions/remove warns
    canBuy() {
      if (!this.myAdmin) return false
      return this.myAdmin.level <= 5
    }
  },
  mounted() {
    this.loadData()
  },
  methods: {
    async loadData() {
      try {
        const [meRes, listRes] = await Promise.all([
          axios.get('/api/admin/me'),
          axios.get('/api/admin/list')
        ])
        this.myAdmin = meRes.data.admin
        this.adminList = listRes.data
      } catch (err) {
        if (err.response?.status === 403) {
          this.error = 'no perm'
        } else if (err.response?.status === 404) {
          this.error = 'youre not an admin lil blud 😭🙏'
        } else {
          this.error = 'error loading data'
        }
      } finally {
        this.loading = false
      }
    },
    getAdminsByLevel(level) {
      if (!this.adminList?.admins) return []
      return this.adminList.admins.filter(a => a.level === level)
    },
    goBack() {
      localStorage.removeItem('admin_session')
      this.$router.push('/dashboard')
    }
  }
}
</script>

<style scoped>
.admin-panel {
  padding: 20px;
  color: #fff;
  font-family: monospace;
}
nav {
  margin: 20px 0;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
nav button {
  padding: 8px 12px;
  background: #333;
  color: #fff;
  border: 1px solid #555;
  cursor: pointer;
}
nav button:hover {
  background: #444;
}
nav button.active {
  background: #666;
  border-color: #888;
}
.page {
  margin-top: 20px;
}
table {
  border-collapse: collapse;
  margin: 10px 0;
}
td, th {
  border: 1px solid #444;
  padding: 6px 12px;
  text-align: left;
}
th {
  background: #333;
}
h2, h3 {
  margin-top: 20px;
}
</style>
