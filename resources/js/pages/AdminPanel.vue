<template>
  <div class="admin-panel">
    <div v-if="loading">{{ $t('common.loading') }}</div>
    <div v-else-if="error">{{ error }} <button @click="goBack">{{ $t('common.back') }}</button></div>
    
    <div v-else>
      <div class="header-row">
        <h1>{{ $t('admin.title') }}</h1>
        <select v-model="locale" @change="changeLocale" class="lang-switch">
          <option value="en">EN</option>
          <option value="ru">RU</option>
        </select>
      </div>
      <p>{{ myAdmin.name }} | {{ $t('admin.level') }} {{ myAdmin.level }}{{ myAdmin.is_ga ? '+' : '' }}</p>
      
      <nav>
        <button @click="switchPage('home')" :class="{ active: currentPage === 'home' }">{{ $t('admin.nav.home') }}</button>
        <button @click="switchPage('admins')" :class="{ active: currentPage === 'admins' }">{{ $t('admin.nav.admins') }}</button>
        
        <!-- 6+ -->
        <template v-if="canViewLogs">
          <button @click="switchPage('actions')" :class="{ active: currentPage === 'actions' }">{{ $t('admin.nav.actions') }}</button>
          <button @click="switchPage('warnings')" :class="{ active: currentPage === 'warnings' }">{{ $t('admin.nav.warnings') }}</button>
          <button @click="switchPage('purchases')" :class="{ active: currentPage === 'purchases' }">{{ $t('admin.nav.purchases') }}</button>
        </template>
        
        <!-- 7+ -->
        <template v-if="canViewRemoved">
          <button @click="switchPage('removed')" :class="{ active: currentPage === 'removed' }">{{ $t('admin.nav.removed') }}</button>
        </template>
        
        <!-- 8lvl -->
        <template v-if="canViewGAActions">
          <button @click="switchPage('ga-actions')" :class="{ active: currentPage === 'ga-actions' }">{{ $t('admin.nav.ga_actions') }}</button>
        </template>
        
        <!-- 8+ -->
        <template v-if="canManageServers">
          <button @click="switchPage('news')" :class="{ active: currentPage === 'news' }">{{ $t('admin.nav.news') }}</button>
          <button @click="switchPage('servers')" :class="{ active: currentPage === 'servers' }">{{ $t('admin.nav.servers') }}</button>
        </template>
        
        <button @click="goBack">← {{ $t('common.exit') }}</button>
      </nav>

      <!-- home -->
      <div v-if="currentPage === 'home'" class="page">
        <h2>{{ $t('admin.home.title') }}</h2>
        <table>
          <tr><td>{{ $t('admin.home.level') }}:</td><td>{{ myAdmin.level }}</td></tr>
          <tr><td>{{ $t('admin.ga') }}:</td><td>{{ myAdmin.is_ga ? $t('common.yes') : $t('common.no') }}</td></tr>
          <tr><td>{{ $t('admin.home.warnings') }}:</td><td>{{ myAdmin.warnings }}/3</td></tr>
          <tr><td>{{ $t('admin.home.appointed_by') }}:</td><td>{{ myAdmin.appointed_by }}</td></tr>
          <tr><td>{{ $t('admin.home.appointed_date') }}:</td><td>{{ myAdmin.appointed_date }}</td></tr>
          <tr><td>{{ $t('admin.home.last_online') }}:</td><td>{{ myAdmin.last_online }}</td></tr>
          <tr><td>{{ $t('admin.home.confirmed') }}:</td><td>{{ myAdmin.is_confirmed ? $t('common.yes') : $t('common.no') }}</td></tr>
          <tr><td>{{ $t('admin.home.reputation') }}:</td><td>+{{ myAdmin.reputation.up }} / -{{ myAdmin.reputation.down }}</td></tr>
        </table>

        <h3>{{ $t('admin.home.playtime') }}</h3>
        <table>
          <tr><td>{{ $t('admin.home.today') }}:</td><td>{{ myAdmin.playtime.today }}</td></tr>
          <tr><td>{{ $t('admin.home.yesterday') }}:</td><td>{{ myAdmin.playtime.yesterday }}</td></tr>
          <tr><td>{{ $t('admin.home.day_before') }}:</td><td>{{ myAdmin.playtime.day_before }}</td></tr>
          <tr><td>{{ $t('admin.home.week') }}:</td><td>{{ myAdmin.playtime.week }}</td></tr>
        </table>

        <template v-if="myAdmin.stats">
          <h3>{{ $t('admin.home.stats') }}</h3>
          <table>
            <tr><td>{{ $t('admin.home.hours_played') }}:</td><td>{{ myAdmin.stats.hours_played }}</td></tr>
            <tr><td>{{ $t('admin.home.punishments') }}:</td><td>{{ myAdmin.stats.punishments_given }}</td></tr>
            <tr><td>{{ $t('admin.home.reports_answered') }}:</td><td>{{ myAdmin.stats.reports_answered }}</td></tr>
          </table>
        </template>

        <template v-if="canBuy">
          <h3>{{ $t('admin.home.shop') }}</h3>
          <p v-if="myAdmin.level < 5">{{ $t('admin.home.promotion') }}: {{ $t('admin.home.available') }}</p>
          <p>{{ $t('admin.home.remove_warning') }}: {{ myAdmin.warnings > 0 ? $t('admin.home.available') : $t('admin.home.no_warns') }}</p>
          <p><i>{{ $t('admin.home.payment_not_ready') }}</i></p>
        </template>
      </div>

      <!-- admins list -->
      <div v-if="currentPage === 'admins'" class="page">
        <h2>{{ $t('admin.admins_list.title') }}</h2>
        <p>{{ $t('common.total') }}: {{ adminList?.total || 0 }} | {{ $t('common.online') }}: {{ adminList?.online || 0 }}</p>
        
        <div v-for="level in [6,5,4,3,2,1]" :key="level">
          <h3>{{ $t('admin.admins_list.level_title', { level }) }}</h3>
          <table v-if="getAdminsByLevel(level).length">
            <tr>
              <th>{{ $t('admin.admins_list.name') }}</th>
              <th>{{ $t('admin.admins_list.warns') }}</th>
              <th>{{ $t('admin.admins_list.rep') }}</th>
              <th>{{ $t('admin.admins_list.three_days') }}</th>
              <th>{{ $t('admin.home.week') }}</th>
              <th>{{ $t('admin.admins_list.status') }}</th>
            </tr>
            <tr v-for="admin in getAdminsByLevel(level)" :key="admin.id">
              <td>{{ admin.name }}</td>
              <td>{{ admin.warnings }}/3</td>
              <td>+{{ admin.reputation.up }}/-{{ admin.reputation.down }}</td>
              <td>{{ admin.playtime_3days }}</td>
              <td>{{ admin.playtime_week }}</td>
              <td>{{ admin.is_online ? $t('common.on') : $t('common.off') }}</td>
            </tr>
          </table>
          <p v-else><i>{{ $t('admin.admins_list.no_admins') }}</i></p>
        </div>
      </div>

      <!-- admin actions (6+) -->
      <div v-if="currentPage === 'actions'" class="page">
        <h2>{{ $t('admin.actions.title') }}</h2>
        <div class="filters">
          <input v-model="filters.actions.admin" :placeholder="$t('admin.actions.admin')" />
          <input v-model="filters.actions.player" :placeholder="$t('admin.actions.player')" />
          <input v-model="filters.actions.cmd" :placeholder="$t('admin.actions.cmd')" />
          <button @click="loadActions">{{ $t('common.search') }}</button>
          <span>{{ $t('common.page') }}: {{ actionsData.page || 0 }}</span>
          <button @click="actionsPage(-1)">←</button>
          <button @click="actionsPage(1)">→</button>
        </div>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <table v-else-if="actionsData.data?.length">
          <tr>
            <th>{{ $t('admin.actions.admin') }}</th>
            <th>{{ $t('admin.actions.player') }}</th>
            <th>{{ $t('admin.actions.cmd') }}</th>
            <th>{{ $t('admin.actions.amount') }}</th>
            <th>{{ $t('admin.actions.reason') }}</th>
            <th>{{ $t('admin.actions.date') }}</th>
          </tr>
          <tr v-for="row in actionsData.data" :key="row.id">
            <td>{{ row.admin }}</td>
            <td>{{ row.player }}</td>
            <td>{{ row.cmd }}</td>
            <td>{{ row.amount }}</td>
            <td>{{ row.reason }}</td>
            <td>{{ row.date }}</td>
          </tr>
        </table>
        <p v-else><i>{{ $t('common.nothing') }}</i></p>
      </div>

      <!-- warnings (6+) -->
      <div v-if="currentPage === 'warnings'" class="page">
        <h2>{{ $t('admin.warnings_page.title') }}</h2>
        <div class="filters">
          <input v-model="filters.warnings.issued_by" :placeholder="$t('admin.warnings_page.issued_by')" />
          <input v-model="filters.warnings.issued_to" :placeholder="$t('admin.warnings_page.issued_to')" />
          <input v-model="filters.warnings.reason" :placeholder="$t('admin.actions.reason')" />
          <button @click="loadWarnings">{{ $t('common.search') }}</button>
        </div>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <table v-else-if="warningsData.length">
          <tr>
            <th>{{ $t('admin.warnings_page.by') }}</th>
            <th>{{ $t('admin.warnings_page.to') }}</th>
            <th>{{ $t('admin.actions.reason') }}</th>
            <th>{{ $t('admin.actions.date') }}</th>
          </tr>
          <tr v-for="row in warningsData" :key="row.id">
            <td>{{ row.admin }}</td>
            <td>{{ row.target }}</td>
            <td>{{ row.reason }}</td>
            <td>{{ row.date }}</td>
          </tr>
        </table>
        <p v-else><i>{{ $t('admin.warnings_page.no_warnings') }}</i></p>
      </div>

      <!-- purchases (6+) -->
      <div v-if="currentPage === 'purchases'" class="page">
        <h2>{{ $t('admin.purchases_page.title') }}</h2>
        <div class="filters">
          <input v-model="filters.purchases.admin" :placeholder="$t('admin.purchases_page.admin')" />
          <input v-model="filters.purchases.vk" :placeholder="$t('admin.purchases_page.vk')" />
          <select v-model="filters.purchases.type">
            <option value="">{{ $t('admin.purchases_page.all_types') }}</option>
            <option value="1">{{ $t('admin.purchases_page.buy_admin') }}</option>
            <option value="2">{{ $t('admin.purchases_page.promotion') }}</option>
            <option value="3">{{ $t('admin.purchases_page.remove_warn') }}</option>
          </select>
          <button @click="loadPurchases">{{ $t('common.search') }}</button>
          <span>{{ $t('common.page') }}: {{ purchasesData.page || 0 }}</span>
          <button @click="purchasesPage(-1)">←</button>
          <button @click="purchasesPage(1)">→</button>
        </div>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <table v-else-if="purchasesData.data?.length">
          <tr>
            <th>{{ $t('admin.purchases_page.admin') }}</th>
            <th>VK</th>
            <th>{{ $t('admin.purchases_page.type') }}</th>
            <th>{{ $t('admin.purchases_page.level') }}</th>
            <th>{{ $t('admin.actions.date') }}</th>
            <th>{{ $t('admin.purchases_page.action') }}</th>
          </tr>
          <tr v-for="row in purchasesData.data" :key="row.id">
            <td>{{ row.name }}</td>
            <td><a :href="row.vk_page" target="_blank">{{ row.vk_page }}</a></td>
            <td>{{ row.type_name }}</td>
            <td>{{ row.level }}</td>
            <td>{{ row.date }}</td>
            <td>
              <button v-if="row.needs_confirm" @click="confirmPurchase(row.name)">{{ $t('common.confirm') }}</button>
              <span v-else>-</span>
            </td>
          </tr>
        </table>
        <p v-else><i>{{ $t('admin.purchases_page.no_purchases') }}</i></p>
      </div>

      <!-- removed admins (7+) -->
      <div v-if="currentPage === 'removed'" class="page">
        <h2>{{ $t('admin.removed_page.title') }}</h2>
        <div class="filters">
          <input v-model="filters.removed.removed" :placeholder="$t('admin.removed_page.removed')" />
          <input v-model="filters.removed.removed_by" :placeholder="$t('admin.removed_page.removed_by')" />
          <input v-model="filters.removed.level" :placeholder="$t('admin.purchases_page.level')" type="number" />
          <button @click="loadRemoved">{{ $t('common.search') }}</button>
        </div>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <table v-else-if="removedData.length">
          <tr>
            <th>{{ $t('admin.removed_page.removed') }}</th>
            <th>{{ $t('admin.warnings_page.by') }}</th>
            <th>{{ $t('admin.purchases_page.level') }}</th>
            <th>{{ $t('admin.actions.reason') }}</th>
            <th>{{ $t('admin.actions.date') }}</th>
          </tr>
          <tr v-for="row in removedData" :key="row.id">
            <td>{{ row.target }}</td>
            <td>{{ row.admin }}</td>
            <td>{{ row.level }}</td>
            <td>{{ row.reason }}</td>
            <td>{{ row.date }}</td>
          </tr>
        </table>
        <p v-else><i>{{ $t('admin.removed_page.no_removed') }}</i></p>
      </div>

      <!-- ga actions (8lvl) -->
      <div v-if="currentPage === 'ga-actions'" class="page">
        <h2>{{ $t('admin.ga_actions_page.title') }}</h2>
        <div class="filters">
          <input v-model="filters.ga.ga" :placeholder="$t('admin.ga_actions_page.ga')" />
          <input v-model="filters.ga.target" :placeholder="$t('admin.ga_actions_page.target')" />
          <select v-model="filters.ga.type">
            <option value="">{{ $t('admin.ga_actions_page.all') }}</option>
            <option value="1">{{ $t('admin.ga_actions_page.warn') }}</option>
            <option value="2">{{ $t('admin.ga_actions_page.unwarn') }}</option>
            <option value="3">{{ $t('admin.ga_actions_page.promote') }}</option>
            <option value="4">{{ $t('admin.ga_actions_page.demote') }}</option>
            <option value="5">{{ $t('admin.ga_actions_page.remove') }}</option>
            <option value="6">{{ $t('admin.ga_actions_page.appoint') }}</option>
          </select>
          <button @click="loadGAActions">{{ $t('common.search') }}</button>
          <span>{{ $t('common.page') }}: {{ gaActionsData.page || 0 }}</span>
          <button @click="gaActionsPage(-1)">←</button>
          <button @click="gaActionsPage(1)">→</button>
        </div>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <table v-else-if="gaActionsData.data?.length">
          <tr>
            <th>{{ $t('admin.ga') }}</th>
            <th>{{ $t('admin.ga_actions_page.target') }}</th>
            <th>{{ $t('admin.purchases_page.action') }}</th>
            <th>{{ $t('admin.actions.amount') }}</th>
            <th>{{ $t('admin.actions.reason') }}</th>
            <th>{{ $t('admin.actions.date') }}</th>
          </tr>
          <tr v-for="row in gaActionsData.data" :key="row.id">
            <td>{{ row.admin }}</td>
            <td>{{ row.target }}</td>
            <td>{{ row.type_name }}</td>
            <td>{{ row.amount }}</td>
            <td>{{ row.reason }}</td>
            <td>{{ row.date }}</td>
          </tr>
        </table>
        <p v-else><i>{{ $t('common.nothing') }}</i></p>
      </div>

      <!-- news (8+) -->
      <div v-if="currentPage === 'news'" class="page">
        <h2>{{ $t('admin.news_page.title') }}</h2>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <div v-else>
          <button @click="showNewsForm = !showNewsForm">{{ showNewsForm ? $t('common.cancel') : $t('admin.news_page.add_new') }}</button>
          
          <div v-if="showNewsForm" class="news-form">
            <input v-model="newsForm.title" :placeholder="$t('admin.news_page.title_field')" />
            <textarea v-model="newsForm.message" :placeholder="$t('admin.news_page.external_msg')"></textarea>
            <textarea v-model="newsForm.message2" :placeholder="$t('admin.news_page.internal_msg')"></textarea>
            <button @click="saveNews">{{ newsForm.id ? $t('common.save') : $t('common.create') }}</button>
          </div>
          
          <table v-if="newsData.length">
            <tr>
              <th>{{ $t('admin.news_page.title_field') }}</th>
              <th>{{ $t('admin.news_page.author') }}</th>
              <th>{{ $t('admin.actions.date') }}</th>
              <th>{{ $t('admin.news_page.actions') }}</th>
            </tr>
            <tr v-for="item in newsData" :key="item.id">
              <td>{{ item.title }}</td>
              <td>{{ item.author }}</td>
              <td>{{ item.date }}</td>
              <td>
                <button @click="editNews(item)">{{ $t('common.edit') }}</button>
                <button @click="deleteNews(item.id)">{{ $t('common.delete') }}</button>
              </td>
            </tr>
          </table>
          <p v-else><i>{{ $t('admin.news_page.no_news') }}</i></p>
        </div>
      </div>

      <!-- servers (8+) -->
      <div v-if="currentPage === 'servers'" class="page">
        <h2>{{ $t('admin.servers_page.title') }}</h2>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <div v-else-if="serversData">
          <div v-for="(settings, server) in serversData" :key="server" class="server-block">
            <h3>{{ server }}</h3>
            <table v-if="settings">
              <tr>
                <td>{{ $t('admin.servers_page.donate_multiplier') }}:</td>
                <td>
                  <select v-model="settings.donate_multiplier">
                    <option :value="0">{{ $t('common.off') }}</option>
                    <option :value="1">x2</option>
                    <option :value="2">x3</option>
                  </select>
                </td>
              </tr>
              <tr>
                <td>{{ $t('admin.servers_page.discounts') }}:</td>
                <td><input type="checkbox" v-model="settings.discounts_enabled" /></td>
              </tr>
              <tr>
                <td>{{ $t('admin.servers_page.ads') }}:</td>
                <td><input type="checkbox" v-model="settings.ads_enabled" /></td>
              </tr>
              <tr v-if="settings.ads_enabled">
                <td>{{ $t('admin.servers_page.ads_link') }}:</td>
                <td><input v-model="settings.ads_link" /></td>
              </tr>
              <tr v-if="settings.ads_enabled">
                <td>{{ $t('admin.servers_page.ads_desc') }}:</td>
                <td><input v-model="settings.ads_description" /></td>
              </tr>
              <tr>
                <td></td>
                <td><button @click="saveServer(server, settings)">{{ $t('common.save') }}</button></td>
              </tr>
            </table>
            <p v-else><i>{{ $t('admin.servers_page.failed_load') }}</i></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { setLocale, getLocale } from '../i18n'

export default {
  name: 'AdminPanel',
  data() {
    return {
      myAdmin: null,
      adminList: null,
      loading: true,
      pageLoading: false,
      error: null,
      currentPage: 'home',
      locale: getLocale(),
      
      actionsData: {},
      warningsData: [],
      purchasesData: {},
      removedData: [],
      gaActionsData: {},
      serversData: null,
      newsData: [],
      showNewsForm: false,
      newsForm: { id: null, title: '', message: '', message2: '' },
      
      filters: {
        actions: { admin: '', player: '', cmd: '', page: 0 },
        warnings: { issued_by: '', issued_to: '', reason: '' },
        purchases: { admin: '', vk: '', type: '', page: 0 },
        removed: { removed: '', removed_by: '', level: '' },
        ga: { ga: '', target: '', type: '', page: 0 }
      }
    }
  },
  computed: {
    canViewLogs() {
      if (!this.myAdmin) return false
      return this.myAdmin.level >= 7 || (this.myAdmin.level === 6 && this.myAdmin.is_ga)
    },
    canViewRemoved() {
      if (!this.myAdmin) return false
      return this.myAdmin.level >= 7
    },
    canViewGAActions() {
      if (!this.myAdmin) return false
      return this.myAdmin.level >= 8
    },
    canManageServers() {
      if (!this.myAdmin) return false
      return this.myAdmin.level === 8 && this.myAdmin.is_ga
    },
    canBuy() {
      if (!this.myAdmin) return false
      return this.myAdmin.level <= 5
    }
  },
  mounted() {
    this.loadData()
  },
  methods: {
    changeLocale() {
      setLocale(this.locale)
    },
    
    async loadData() {
      try {
        const [meRes, listRes] = await Promise.all([
          axios.get('/api/admin/me'),
          axios.get('/api/admin/list')
        ])
        this.myAdmin = meRes.data.admin
        this.adminList = listRes.data
      } catch (err) {
        this.error = err.response?.status === 403 ? this.$t('errors.no_access') : 
                     err.response?.status === 404 ? this.$t('errors.not_admin') : this.$t('errors.failed_load')
      } finally {
        this.loading = false
      }
    },
    
    getAdminsByLevel(level) {
      if (!this.adminList?.admins) return []
      return this.adminList.admins.filter(a => a.level === level)
    },
    
    switchPage(page) {
      this.currentPage = page
      if (page === 'actions' && !this.actionsData.data) this.loadActions()
      if (page === 'warnings' && !this.warningsData.length) this.loadWarnings()
      if (page === 'purchases' && !this.purchasesData.data) this.loadPurchases()
      if (page === 'removed' && !this.removedData.length) this.loadRemoved()
      if (page === 'ga-actions' && !this.gaActionsData.data) this.loadGAActions()
      if (page === 'servers' && !this.serversData) this.loadServers()
      if (page === 'news' && !this.newsData.length) this.loadNews()
    },
    
    async loadActions() {
      this.pageLoading = true
      try {
        const params = new URLSearchParams()
        if (this.filters.actions.admin) params.append('admin', this.filters.actions.admin)
        if (this.filters.actions.player) params.append('player', this.filters.actions.player)
        if (this.filters.actions.cmd) params.append('cmd', this.filters.actions.cmd)
        params.append('page', this.filters.actions.page)
        
        const res = await axios.get('/api/admin/logs/actions?' + params)
        this.actionsData = res.data
      } catch (e) {
        console.warn('failed to load actions')
      } finally {
        this.pageLoading = false
      }
    },
    
    actionsPage(delta) {
      this.filters.actions.page = Math.max(0, this.filters.actions.page + delta)
      this.loadActions()
    },
    
    async loadWarnings() {
      this.pageLoading = true
      try {
        const params = new URLSearchParams()
        if (this.filters.warnings.issued_by) params.append('issued_by', this.filters.warnings.issued_by)
        if (this.filters.warnings.issued_to) params.append('issued_to', this.filters.warnings.issued_to)
        if (this.filters.warnings.reason) params.append('reason', this.filters.warnings.reason)
        
        const res = await axios.get('/api/admin/logs/warnings?' + params)
        this.warningsData = res.data.data || []
      } catch (e) {
        console.warn('failed to load warnings')
      } finally {
        this.pageLoading = false
      }
    },
    
    async loadPurchases() {
      this.pageLoading = true
      try {
        const params = new URLSearchParams()
        if (this.filters.purchases.admin) params.append('admin', this.filters.purchases.admin)
        if (this.filters.purchases.vk) params.append('vk', this.filters.purchases.vk)
        if (this.filters.purchases.type) params.append('type', this.filters.purchases.type)
        params.append('page', this.filters.purchases.page)
        
        const res = await axios.get('/api/admin/logs/purchases?' + params)
        this.purchasesData = res.data
      } catch (e) {
        console.warn('failed to load purchases')
      } finally {
        this.pageLoading = false
      }
    },
    
    purchasesPage(delta) {
      this.filters.purchases.page = Math.max(0, this.filters.purchases.page + delta)
      this.loadPurchases()
    },
    
    async confirmPurchase(adminName) {
      try {
        await axios.post('/api/admin/logs/purchases/confirm', { admin_name: adminName })
        this.loadPurchases()
      } catch (e) {
        alert(this.$t('errors.failed_save'))
      }
    },
    
    async loadRemoved() {
      this.pageLoading = true
      try {
        const params = new URLSearchParams()
        if (this.filters.removed.removed) params.append('removed', this.filters.removed.removed)
        if (this.filters.removed.removed_by) params.append('removed_by', this.filters.removed.removed_by)
        if (this.filters.removed.level) params.append('level', this.filters.removed.level)
        
        const res = await axios.get('/api/admin/logs/removed?' + params)
        this.removedData = res.data.data || []
      } catch (e) {
        console.warn('failed to load removed')
      } finally {
        this.pageLoading = false
      }
    },
    
    async loadGAActions() {
      this.pageLoading = true
      try {
        const params = new URLSearchParams()
        if (this.filters.ga.ga) params.append('ga', this.filters.ga.ga)
        if (this.filters.ga.target) params.append('target', this.filters.ga.target)
        if (this.filters.ga.type) params.append('type', this.filters.ga.type)
        params.append('page', this.filters.ga.page)
        
        const res = await axios.get('/api/admin/logs/ga-actions?' + params)
        this.gaActionsData = res.data
      } catch (e) {
        console.warn('failed to load ga actions')
      } finally {
        this.pageLoading = false
      }
    },
    
    gaActionsPage(delta) {
      this.filters.ga.page = Math.max(0, this.filters.ga.page + delta)
      this.loadGAActions()
    },
    
    async loadServers() {
      this.pageLoading = true
      try {
        const res = await axios.get('/api/admin/servers')
        this.serversData = res.data.servers
      } catch (e) {
        console.warn('failed to load servers')
      } finally {
        this.pageLoading = false
      }
    },
    
    async saveServer(server, settings) {
      try {
        await axios.post('/api/admin/servers', {
          server,
          donate_multiplier: settings.donate_multiplier,
          discounts_enabled: settings.discounts_enabled,
          ads_enabled: settings.ads_enabled,
          ads_link: settings.ads_link,
          ads_description: settings.ads_description
        })
        alert('saved')
      } catch (e) {
        alert(this.$t('errors.failed_save'))
      }
    },
    
    async loadNews() {
      this.pageLoading = true
      try {
        const res = await axios.get('/api/admin/news')
        this.newsData = res.data.news || []
      } catch (e) {
        console.warn('failed to load news')
      } finally {
        this.pageLoading = false
      }
    },
    
    editNews(item) {
      this.newsForm = { ...item }
      this.showNewsForm = true
    },
    
    async saveNews() {
      try {
        if (this.newsForm.id) {
          await axios.put('/api/admin/news/' + this.newsForm.id, this.newsForm)
        } else {
          await axios.post('/api/admin/news', this.newsForm)
        }
        this.showNewsForm = false
        this.newsForm = { id: null, title: '', message: '', message2: '' }
        this.loadNews()
      } catch (e) {
        alert(this.$t('errors.failed_save'))
      }
    },
    
    async deleteNews(id) {
      if (!confirm(this.$t('admin.news_page.delete_confirm'))) return
      try {
        await axios.delete('/api/admin/news/' + id)
        this.loadNews()
      } catch (e) {
        alert(this.$t('errors.failed_save'))
      }
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
.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.lang-switch {
  padding: 4px 8px;
  background: #333;
  color: #fff;
  border: 1px solid #555;
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
nav button:hover { background: #444; }
nav button.active { background: #666; border-color: #888; }
.page { margin-top: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
td, th { border: 1px solid #444; padding: 6px 12px; text-align: left; }
th { background: #333; }
h2, h3 { margin-top: 20px; }
.filters { margin-bottom: 15px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.filters input, .filters select { padding: 6px; background: #222; color: #fff; border: 1px solid #444; }
.filters button { padding: 6px 12px; background: #444; color: #fff; border: 1px solid #555; cursor: pointer; }
.server-block { margin-bottom: 30px; padding: 15px; background: #1a1a1a; border: 1px solid #333; }
.news-form { margin: 15px 0; padding: 15px; background: #1a1a1a; border: 1px solid #333; }
.news-form input, .news-form textarea { width: 100%; margin-bottom: 10px; padding: 8px; background: #222; color: #fff; border: 1px solid #444; }
.news-form textarea { min-height: 100px; }
</style>
