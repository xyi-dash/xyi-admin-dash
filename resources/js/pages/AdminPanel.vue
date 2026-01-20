<template>
  <div class="admin-panel">
    <div v-if="loading">{{ $t('common.loading') }}</div>
    <div v-else-if="error">{{ error }} <button @click="goBack">{{ $t('common.back') }}</button></div>
    
    <div v-else>
      <div class="header-row">
        <h1>{{ $t('admin.title') }}</h1>
        <div class="header-controls">
          <!-- server switcher (7+) -->
          <select v-if="extServers.length > 1" v-model="extCurrentServer" @change="onServerChange" class="server-switch">
            <option v-for="s in extServers" :key="s.id" :value="s.id">
              {{ s.name }}
            </option>
          </select>
          <span v-else-if="extServers.length === 1" class="server-label">{{ extServers[0].name }}</span>
          <select v-model="locale" @change="changeLocale" class="lang-switch">
            <option value="en">EN</option>
            <option value="ru">RU</option>
          </select>
        </div>
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
          <button @click="switchPage('extended')" :class="{ active: currentPage.startsWith('ext-') || currentPage === 'extended' }">{{ $t('admin.nav.extended') }}</button>
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
        
        <div v-for="level in availableLevels" :key="level">
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
              <td><a href="#" @click.prevent="openManage(admin.name)" class="admin-link">{{ admin.name }}</a></td>
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
          <span>{{ $t('common.page') }}: {{ (actionsData.page || 0) + 1 }}</span>
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
          <span>{{ $t('common.page') }}: {{ (purchasesData.page || 0) + 1 }}</span>
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
          <span>{{ $t('common.page') }}: {{ (gaActionsData.page || 0) + 1 }}</span>
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

      <!-- extended menu (7+) -->
      <div v-if="currentPage === 'extended'" class="page">
        <h2>{{ $t('admin.extended.title') }}</h2>
        <div class="ext-menu">
          <button @click="switchPage('ext-player-search')">{{ $t('admin.nav.player_search') }}</button>
          <button @click="switchPage('ext-reputation')">{{ $t('admin.nav.reputation_logs') }}</button>
          <button @click="switchPage('ext-nicknames')">{{ $t('admin.nav.nickname_logs') }}</button>
          <button @click="switchPage('ext-unbans')">{{ $t('admin.nav.unban_logs') }}</button>
          <button @click="switchPage('ext-bans')">{{ $t('admin.nav.permanent_bans') }}</button>
          <button @click="switchPage('ext-ip-bans')">{{ $t('admin.nav.ip_bans') }}</button>
          <button @click="switchPage('ext-matchmaking')">{{ $t('admin.nav.matchmaking') }}</button>
          <button @click="switchPage('ext-money')">{{ $t('admin.nav.money_logs') }}</button>
          <button @click="switchPage('ext-accessories')">{{ $t('admin.nav.accessory_logs') }}</button>
        </div>
      </div>

      <!-- player search -->
      <div v-if="currentPage === 'ext-player-search'" class="page">
        <button @click="switchPage('extended')" class="back-btn">← {{ $t('common.back') }}</button>
        <h2>{{ $t('admin.extended.player_search.title') }}</h2>
        <div class="filters">
          <input v-model="extFilters.playerSearch.nickname" :placeholder="$t('admin.extended.player_search.by_nick')" />
          <input v-model="extFilters.playerSearch.account_id" :placeholder="$t('admin.extended.player_search.by_id')" type="number" />
          <button @click="searchPlayer">{{ $t('common.search') }}</button>
        </div>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <table v-else-if="extData.playerSearch.length">
          <tr>
            <th>ID</th>
            <th>{{ $t('admin.admins_list.name') }}</th>
            <th>{{ $t('admin.extended.player_stats.level') }}</th>
            <th>{{ $t('admin.extended.player_stats.kills') }}</th>
            <th>{{ $t('admin.extended.player_stats.deaths') }}</th>
            <th>{{ $t('admin.extended.player_stats.donate') }}</th>
          </tr>
          <tr v-for="p in extData.playerSearch" :key="p.id">
            <td><a href="#" @click.prevent="viewPlayer(p.id)">{{ p.id }}</a></td>
            <td>{{ p.name }}</td>
            <td>{{ p.level }}</td>
            <td>{{ p.kills }}</td>
            <td>{{ p.deaths }}</td>
            <td>{{ p.donate_money }}</td>
          </tr>
        </table>
        <p v-else-if="extData.playerSearched"><i>{{ $t('admin.extended.player_search.not_found') }}</i></p>
      </div>

      <!-- player stats -->
      <div v-if="currentPage === 'ext-player-stats'" class="page">
        <button @click="switchPage('ext-player-search')" class="back-btn">← {{ $t('common.back') }}</button>
        <h2>{{ $t('admin.extended.player_stats.title') }}</h2>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <div v-else-if="extData.playerStats">
          <table>
            <tr><td>{{ $t('admin.extended.player_stats.account_id') }}:</td><td>{{ extData.playerStats.id }}</td></tr>
            <tr><td>{{ $t('admin.admins_list.name') }}:</td><td>{{ extData.playerStats.name }}</td></tr>
            <tr><td>{{ $t('admin.extended.player_stats.email') }}:</td><td>{{ extData.playerStats.email || '-' }} {{ extData.playerStats.email_verified ? '✓' : '' }}</td></tr>
            <tr><td>{{ $t('admin.extended.player_stats.registered') }}:</td><td>{{ extData.playerStats.registered_at }}</td></tr>
            <tr><td>{{ $t('admin.extended.player_stats.last_online') }}:</td><td>{{ extData.playerStats.last_online }}</td></tr>
            <tr><td>{{ $t('admin.extended.player_stats.ip_last') }}:</td><td>{{ extData.playerStats.ip_last }}</td></tr>
            <tr><td>{{ $t('admin.extended.player_stats.ip_reg') }}:</td><td>{{ extData.playerStats.ip_reg }}</td></tr>
            <tr><td>{{ $t('admin.extended.player_stats.level') }}:</td><td>{{ extData.playerStats.level }}</td></tr>
            <tr><td>{{ $t('admin.extended.player_stats.cash') }}:</td><td>{{ extData.playerStats.cash }}</td></tr>
            <tr><td>{{ $t('admin.extended.player_stats.kills') }}:</td><td>{{ extData.playerStats.kills }}</td></tr>
            <tr><td>{{ $t('admin.extended.player_stats.deaths') }}:</td><td>{{ extData.playerStats.deaths }}</td></tr>
            <tr><td>{{ $t('admin.extended.player_stats.kd') }}:</td><td>{{ extData.playerStats.kd }}</td></tr>
            <tr><td>{{ $t('admin.extended.player_stats.donate') }}:</td><td>{{ extData.playerStats.donate?.money || 0 }}</td></tr>
            <tr><td>{{ $t('admin.extended.player_stats.rank') }}:</td><td>{{ $t('admin.extended.ranks.' + extData.playerStats.rank) }}</td></tr>
          </table>
          <h3>{{ $t('admin.extended.player_stats.gangwar') }}</h3>
          <table>
            <tr><td>Grove:</td><td>{{ extData.playerStats.gangwar?.grove }}</td></tr>
            <tr><td>Ballas:</td><td>{{ extData.playerStats.gangwar?.ballas }}</td></tr>
            <tr><td>Vagos:</td><td>{{ extData.playerStats.gangwar?.vagos }}</td></tr>
            <tr><td>Aztec:</td><td>{{ extData.playerStats.gangwar?.aztec }}</td></tr>
          </table>
        </div>
      </div>

      <!-- reputation logs -->
      <div v-if="currentPage === 'ext-reputation'" class="page">
        <button @click="switchPage('extended')" class="back-btn">← {{ $t('common.back') }}</button>
        <h2>{{ $t('admin.extended.reputation.title') }}</h2>
        <div class="filters">
          <input v-model="extFilters.reputation.from" :placeholder="$t('admin.extended.reputation.from')" />
          <input v-model="extFilters.reputation.to" :placeholder="$t('admin.extended.reputation.to')" />
          <button @click="loadReputation">{{ $t('common.search') }}</button>
        </div>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <table v-else-if="extData.reputation.length">
          <tr>
            <th>{{ $t('admin.extended.reputation.from') }}</th>
            <th>{{ $t('admin.extended.reputation.to') }}</th>
            <th>{{ $t('admin.extended.reputation.type') }}</th>
            <th>{{ $t('admin.extended.reputation.comment') }}</th>
            <th>{{ $t('admin.extended.reputation.date') }}</th>
          </tr>
          <tr v-for="r in extData.reputation" :key="r.id">
            <td>{{ r.from }} <span v-if="r.from_is_banned" class="ban-badge">BAN</span></td>
            <td>{{ r.to }} <span v-if="r.to_is_banned" class="ban-badge">BAN</span></td>
            <td>{{ r.type }}</td>
            <td>{{ r.comment }}</td>
            <td>{{ r.date }}</td>
          </tr>
        </table>
        <p v-else><i>{{ $t('admin.extended.reputation.no_logs') }}</i></p>
      </div>

      <!-- nickname logs -->
      <div v-if="currentPage === 'ext-nicknames'" class="page">
        <button @click="switchPage('extended')" class="back-btn">← {{ $t('common.back') }}</button>
        <h2>{{ $t('admin.extended.nicknames.title') }}</h2>
        <div class="filters">
          <input v-model="extFilters.nicknames.account_id" :placeholder="$t('admin.extended.nicknames.account_id')" type="number" />
          <input v-model="extFilters.nicknames.old_nick" :placeholder="$t('admin.extended.nicknames.old_nick')" />
          <input v-model="extFilters.nicknames.new_nick" :placeholder="$t('admin.extended.nicknames.new_nick')" />
          <button @click="loadNicknames">{{ $t('common.search') }}</button>
        </div>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <table v-else-if="extData.nicknames.length">
          <tr>
            <th>{{ $t('admin.extended.nicknames.account_id') }}</th>
            <th>{{ $t('admin.extended.nicknames.old_nick') }}</th>
            <th>{{ $t('admin.extended.nicknames.new_nick') }}</th>
            <th>{{ $t('admin.extended.nicknames.approved_by') }}</th>
            <th>{{ $t('admin.extended.nicknames.date') }}</th>
          </tr>
          <tr v-for="n in extData.nicknames" :key="n.id">
            <td>{{ n.account_id }}</td>
            <td>{{ n.old_nick }}</td>
            <td>{{ n.new_nick }}</td>
            <td>{{ n.approved_by }}</td>
            <td>{{ n.date }}</td>
          </tr>
        </table>
        <p v-else><i>{{ $t('admin.extended.nicknames.no_logs') }}</i></p>
      </div>

      <!-- unbans -->
      <div v-if="currentPage === 'ext-unbans'" class="page">
        <button @click="switchPage('extended')" class="back-btn">← {{ $t('common.back') }}</button>
        <h2>{{ $t('admin.extended.unbans.title') }}</h2>
        <div class="filters">
          <input v-model="extFilters.unbans.player" :placeholder="$t('admin.extended.unbans.player')" />
          <button @click="loadUnbans">{{ $t('common.search') }}</button>
        </div>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <table v-else-if="extData.unbans.length">
          <tr>
            <th>{{ $t('admin.admins_list.name') }}</th>
            <th>{{ $t('admin.extended.unbans.date') }}</th>
          </tr>
          <tr v-for="u in extData.unbans" :key="u.id">
            <td>{{ u.name }}</td>
            <td>{{ u.date }}</td>
          </tr>
        </table>
        <p v-else><i>{{ $t('admin.extended.unbans.no_logs') }}</i></p>
      </div>

      <!-- permanent bans -->
      <div v-if="currentPage === 'ext-bans'" class="page">
        <button @click="switchPage('extended')" class="back-btn">← {{ $t('common.back') }}</button>
        <h2>{{ $t('admin.extended.bans.title') }}</h2>
        <div class="filters">
          <input v-model="extFilters.bans.player" :placeholder="$t('admin.extended.bans.player')" />
          <input v-model="extFilters.bans.admin" :placeholder="$t('admin.extended.bans.admin')" />
          <button @click="loadBans">{{ $t('common.search') }}</button>
        </div>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <table v-else-if="extData.bans.length">
          <tr>
            <th>{{ $t('admin.extended.bans.admin') }}</th>
            <th>{{ $t('admin.extended.ip_bans.admin_ip') }}</th>
            <th>{{ $t('admin.admins_list.name') }}</th>
            <th>IP</th>
            <th>{{ $t('admin.extended.bans.reason') }}</th>
            <th>{{ $t('admin.extended.bans.date') }}</th>
          </tr>
          <tr v-for="b in extData.bans" :key="b.id">
            <td>{{ b.admin }}</td>
            <td>{{ b.admin_ip }}</td>
            <td>{{ b.name }}</td>
            <td>{{ b.player_ip }}</td>
            <td>{{ b.reason }}</td>
            <td>{{ b.date }}</td>
          </tr>
        </table>
        <p v-else><i>{{ $t('admin.extended.bans.no_bans') }}</i></p>
      </div>

      <!-- ip bans -->
      <div v-if="currentPage === 'ext-ip-bans'" class="page">
        <button @click="switchPage('extended')" class="back-btn">← {{ $t('common.back') }}</button>
        <h2>{{ $t('admin.extended.ip_bans.title') }}</h2>
        <div class="filters">
          <input v-model="extFilters.ipBans.ip" :placeholder="$t('admin.extended.ip_bans.ip')" />
          <input v-model="extFilters.ipBans.admin" :placeholder="$t('admin.extended.ip_bans.admin')" />
          <button @click="loadIPBans">{{ $t('common.search') }}</button>
        </div>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <table v-else-if="extData.ipBans.length">
          <tr>
            <th>{{ $t('admin.extended.ip_bans.ip') }}</th>
            <th>{{ $t('admin.extended.bans.admin') }}</th>
            <th>{{ $t('admin.extended.ip_bans.admin_ip') }}</th>
            <th>{{ $t('admin.extended.ip_bans.date') }}</th>
          </tr>
          <tr v-for="b in extData.ipBans" :key="b.id">
            <td>{{ b.banned_ip }}</td>
            <td>{{ b.admin }}</td>
            <td>{{ b.admin_ip }}</td>
            <td>{{ b.date }}</td>
          </tr>
        </table>
        <p v-else><i>{{ $t('admin.extended.ip_bans.no_bans') }}</i></p>
      </div>

      <!-- matchmaking -->
      <div v-if="currentPage === 'ext-matchmaking'" class="page">
        <button @click="switchPage('extended')" class="back-btn">← {{ $t('common.back') }}</button>
        <h2>{{ $t('admin.extended.matchmaking.title') }}</h2>
        <div class="filters">
          <input v-model="extFilters.matchmaking.player" :placeholder="$t('admin.extended.matchmaking.player')" />
          <button @click="loadMatchmaking">{{ $t('common.search') }}</button>
        </div>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <table v-else-if="extData.matchmaking.length">
          <tr>
            <th>{{ $t('admin.admins_list.name') }}</th>
            <th>{{ $t('admin.extended.matchmaking.elo') }}</th>
            <th>{{ $t('admin.extended.matchmaking.games') }}</th>
            <th>{{ $t('admin.extended.matchmaking.wins') }}</th>
            <th>{{ $t('admin.extended.matchmaking.winrate') }}</th>
            <th>{{ $t('admin.extended.matchmaking.kills') }}</th>
            <th>{{ $t('admin.extended.matchmaking.deaths') }}</th>
            <th>{{ $t('admin.extended.matchmaking.mvp') }}</th>
          </tr>
          <tr v-for="m in extData.matchmaking" :key="m.id">
            <td>{{ m.name }}</td>
            <td>{{ m.elo }}</td>
            <td>{{ m.games }}</td>
            <td>{{ m.wins }}</td>
            <td>{{ m.winrate }}%</td>
            <td>{{ m.kills }}</td>
            <td>{{ m.deaths }}</td>
            <td>{{ m.mvp }}</td>
          </tr>
        </table>
        <p v-else><i>{{ $t('admin.extended.matchmaking.no_stats') }}</i></p>
      </div>

      <!-- money transfers -->
      <div v-if="currentPage === 'ext-money'" class="page">
        <button @click="switchPage('extended')" class="back-btn">← {{ $t('common.back') }}</button>
        <h2>{{ $t('admin.extended.money_transfers.title') }}</h2>
        <div class="filters">
          <input v-model="extFilters.money.from_name" :placeholder="$t('admin.extended.money_transfers.from_name')" />
          <input v-model="extFilters.money.to_name" :placeholder="$t('admin.extended.money_transfers.to_name')" />
          <button @click="loadMoney">{{ $t('common.search') }}</button>
        </div>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <table v-else-if="extData.money.length">
          <tr>
            <th>{{ $t('admin.extended.money_transfers.from_name') }}</th>
            <th>{{ $t('admin.extended.money_transfers.to_name') }}</th>
            <th>{{ $t('admin.extended.money_transfers.amount') }}</th>
            <th>{{ $t('admin.extended.money_transfers.date') }}</th>
          </tr>
          <tr v-for="m in extData.money" :key="m.id">
            <td>{{ m.from_name }} <span v-if="m.from_is_banned" class="ban-badge">BAN</span></td>
            <td>{{ m.to_name }} <span v-if="m.to_is_banned" class="ban-badge">BAN</span></td>
            <td>{{ m.amount }}</td>
            <td>{{ m.date }}</td>
          </tr>
        </table>
        <p v-else><i>{{ $t('admin.extended.money_transfers.no_logs') }}</i></p>
      </div>

      <!-- accessories -->
      <div v-if="currentPage === 'ext-accessories'" class="page">
        <button @click="switchPage('extended')" class="back-btn">← {{ $t('common.back') }}</button>
        <h2>{{ $t('admin.extended.accessories.title') }}</h2>
        <div class="filters">
          <input v-model="extFilters.accessories.account_name" :placeholder="$t('admin.extended.accessories.account_name')" />
          <input v-model="extFilters.accessories.accessory" :placeholder="$t('admin.extended.accessories.accessory')" />
          <button @click="loadAccessories">{{ $t('common.search') }}</button>
        </div>
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <table v-else-if="extData.accessories.length">
          <tr>
            <th>{{ $t('admin.extended.accessories.account_id') }}</th>
            <th>{{ $t('admin.admins_list.name') }}</th>
            <th>{{ $t('admin.extended.accessories.accessory_name') }}</th>
            <th>{{ $t('admin.extended.accessories.action') }}</th>
            <th>{{ $t('admin.extended.accessories.ip') }}</th>
            <th>{{ $t('admin.extended.accessories.date') }}</th>
          </tr>
          <tr v-for="a in extData.accessories" :key="a.id">
            <td>{{ a.account_id }}</td>
            <td>{{ a.account_name }}</td>
            <td>{{ a.accessory_name }}</td>
            <td>{{ a.action }}</td>
            <td>{{ a.account_ip }}</td>
            <td>{{ a.date }}</td>
          </tr>
        </table>
        <p v-else><i>{{ $t('admin.extended.accessories.no_logs') }}</i></p>
      </div>

      <!-- manage admin -->
      <div v-if="currentPage === 'manage'" class="page">
        <button @click="switchPage('admins')" class="back-btn">← {{ $t('admin.manage.back_to_list') }}</button>
        <h2>{{ $t('admin.manage.title') }}: {{ manageData.admin?.name }}</h2>
        
        <p v-if="pageLoading">{{ $t('common.loading') }}</p>
        <div v-else-if="manageData.admin">
          <table class="admin-info">
            <tr><td>{{ $t('admin.home.level') }}:</td><td>{{ manageData.admin.level }}</td></tr>
            <tr><td>{{ $t('admin.ga') }}:</td><td>{{ manageData.admin.is_ga ? $t('common.yes') : $t('common.no') }}</td></tr>
            <tr><td>{{ $t('admin.home.warnings') }}:</td><td>{{ manageData.admin.warnings }}/3</td></tr>
            <tr><td>{{ $t('admin.home.confirmed') }}:</td><td>{{ manageData.admin.needs_confirm ? $t('common.no') : $t('common.yes') }}</td></tr>
            <tr><td>{{ $t('admin.home.appointed_by') }}:</td><td>{{ manageData.admin.appointed_by }}</td></tr>
          </table>

          <div v-if="manageData.actions.length" class="manage-form">
            <h3>{{ $t('admin.purchases_page.action') }}</h3>
            <select v-model="manageForm.action">
              <option value="">{{ $t('admin.manage.select_action') }}</option>
              <option v-for="act in manageData.actions" :key="act" :value="act">
                {{ $t('admin.manage.actions.' + act) }}
              </option>
            </select>
            <input 
              v-if="manageForm.action && !['reset_password', 'confirm'].includes(manageForm.action)"
              v-model="manageForm.reason" 
              :placeholder="$t('admin.manage.reason_placeholder')" 
            />
            <button @click="executeAction" :disabled="!manageForm.action || manageExecuting">
              {{ manageExecuting ? '...' : $t('admin.manage.execute') }}
            </button>
            <span v-if="manageMessage" class="manage-msg">{{ manageMessage }}</span>
          </div>
          <p v-else><i>{{ $t('admin.manage.no_actions') }}</i></p>
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
      },
      
      manageData: { admin: null, actions: [] },
      manageForm: { action: '', reason: '' },
      manageExecuting: false,
      manageMessage: '',
      extServers: [],
      extCurrentServer: null,
      extData: {
        playerSearch: [],
        playerSearched: false,
        playerStats: null,
        reputation: [],
        nicknames: [],
        unbans: [],
        bans: [],
        ipBans: [],
        matchmaking: [],
        money: [],
        accessories: []
      },
      extFilters: {
        playerSearch: { nickname: '', account_id: '' },
        reputation: { from: '', to: '' },
        nicknames: { account_id: '', old_nick: '', new_nick: '' },
        unbans: { player: '' },
        bans: { player: '', admin: '' },
        ipBans: { ip: '', admin: '' },
        matchmaking: { player: '' },
        money: { from_name: '', to_name: '' },
        accessories: { account_name: '', accessory: '' }
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
    },
    availableLevels() {
      if (!this.adminList?.admins) return []
      return [...new Set(this.adminList.admins.map(a => a.level))].sort((a, b) => b - a)
    }
  },
  mounted() {
    this.loadData()
    this.initHashRouting()
  },
  beforeUnmount() {
    window.removeEventListener('hashchange', this.onHashChange)
  },
  methods: {
    changeLocale() {
      setLocale(this.locale)
    },
    
    initHashRouting() {
      window.addEventListener('hashchange', this.onHashChange)
      const hash = window.location.hash.slice(1)
      if (hash && hash !== 'home') {
        this.currentPage = hash
        this.triggerPageLoad(hash)
      }
    },
    
    onHashChange() {
      const hash = window.location.hash.slice(1) || 'home'
      if (hash !== this.currentPage) {
        this.currentPage = hash
        this.triggerPageLoad(hash)
      }
    },
    
    updateHash(page) {
      const newHash = page === 'home' ? '' : `#${page}`
      if (window.location.hash !== newHash && window.location.hash !== `#${page}`) {
        const isMajorNav = !this.currentPage.startsWith('ext-') || !page.startsWith('ext-')
        if (isMajorNav) {
          history.pushState(null, '', newHash || window.location.pathname)
        } else {
          history.replaceState(null, '', newHash || window.location.pathname)
        }
      }
    },
    
    async loadData() {
      try {
        const serverParam = this.getServerParam()
        const q = serverParam ? `?${serverParam}` : ''
        
        const [meRes, listRes] = await Promise.all([
          axios.get('/api/admin/me' + q),
          axios.get('/api/admin/list' + q)
        ])
        this.myAdmin = meRes.data.admin
        this.adminList = listRes.data
        if (this.myAdmin.level >= 7 && !this.extServers.length) this.loadExtServers()
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
      this.updateHash(page)
      this.currentPage = page
      this.triggerPageLoad(page)
    },
    
    triggerPageLoad(page) {
      if (page === 'actions' && !this.actionsData.data) this.loadActions()
      if (page === 'warnings' && !this.warningsData.length) this.loadWarnings()
      if (page === 'purchases' && !this.purchasesData.data) this.loadPurchases()
      if (page === 'removed' && !this.removedData.length) this.loadRemoved()
      if (page === 'ga-actions' && !this.gaActionsData.data) this.loadGAActions()
      if (page === 'servers' && !this.serversData) this.loadServers()
      if (page === 'news' && !this.newsData.length) this.loadNews()
      if (page === 'ext-reputation' && !this.extData.reputation.length) this.loadReputation()
      if (page === 'ext-nicknames' && !this.extData.nicknames.length) this.loadNicknames()
      if (page === 'ext-unbans' && !this.extData.unbans.length) this.loadUnbans()
      if (page === 'ext-bans' && !this.extData.bans.length) this.loadBans()
      if (page === 'ext-ip-bans' && !this.extData.ipBans.length) this.loadIPBans()
      if (page === 'ext-matchmaking' && !this.extData.matchmaking.length) this.loadMatchmaking()
      if (page === 'ext-money' && !this.extData.money.length) this.loadMoney()
      if (page === 'ext-accessories' && !this.extData.accessories.length) this.loadAccessories()
    },
    
    async loadActions() {
      this.pageLoading = true
      try {
        const params = new URLSearchParams()
        if (this.filters.actions.admin) params.append('admin', this.filters.actions.admin)
        if (this.filters.actions.player) params.append('player', this.filters.actions.player)
        if (this.filters.actions.cmd) params.append('cmd', this.filters.actions.cmd)
        params.append('page', this.filters.actions.page)
        this.appendServerParam(params)
        
        const res = await axios.get('/api/admin/logs/actions?' + params)
        this.actionsData = res.data
      } catch (e) {
        console.warn('actions went to gensokyo')
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
        this.appendServerParam(params)
        
        const res = await axios.get('/api/admin/logs/warnings?' + params)
        this.warningsData = res.data.data || []
      } catch (e) {
        console.warn('warnings refused to load. mood.')
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
        this.appendServerParam(params)
        
        const res = await axios.get('/api/admin/logs/purchases?' + params)
        this.purchasesData = res.data
      } catch (e) {
        console.warn('purchases said no')
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
        const serverParam = this.getServerParam()
        const q = serverParam ? `?${serverParam}` : ''
        await axios.post('/api/admin/logs/purchases/confirm' + q, { admin_name: adminName })
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
        this.appendServerParam(params)
        
        const res = await axios.get('/api/admin/logs/removed?' + params)
        this.removedData = res.data.data || []
      } catch (e) {
        console.warn('removed admins are hiding')
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
        this.appendServerParam(params)
        
        const res = await axios.get('/api/admin/logs/ga-actions?' + params)
        this.gaActionsData = res.data
      } catch (e) {
        console.warn('ga actions went poof')
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
        console.warn('servers are playing hide and seek')
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
        console.warn('no news is good news i guess')
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
    },
    
    async openManage(adminName) {
      this.currentPage = 'manage'
      this.manageData = { admin: null, actions: [] }
      this.manageForm = { action: '', reason: '' }
      this.manageMessage = ''
      this.pageLoading = true
      
      try {
        const serverParam = this.getServerParam()
        const q = serverParam ? `?${serverParam}` : ''
        const res = await axios.get('/api/admin/manage/' + encodeURIComponent(adminName) + '/actions' + q)
        this.manageData.admin = res.data.admin
        this.manageData.actions = res.data.actions
      } catch (e) {
        console.warn('manage data vanished. reimu pls')
      } finally {
        this.pageLoading = false
      }
    },
    
    async executeAction() {
      if (!this.manageForm.action || !this.manageData.admin) return
      
      const needsReason = !['reset_password', 'confirm'].includes(this.manageForm.action)
      if (needsReason && !this.manageForm.reason.trim()) {
        this.manageMessage = this.$t('admin.actions.reason') + '!'
        return
      }
      
      this.manageExecuting = true
      this.manageMessage = ''
      
      try {
        const serverParam = this.getServerParam()
        const q = serverParam ? `?${serverParam}` : ''
        const res = await axios.post('/api/admin/manage' + q, {
          target_name: this.manageData.admin.name,
          action: this.manageForm.action,
          reason: this.manageForm.reason || ' '
        })
        
        this.manageMessage = this.$t('admin.manage.success')
        this.manageData.admin = res.data.admin
        this.manageForm = { action: '', reason: '' }
        
        this.loadData()
      } catch (e) {
        const errKey = e.response?.data?.error
        this.manageMessage = errKey ? this.$t('admin.manage.errors.' + errKey) : this.$t('errors.failed_save')
      } finally {
        this.manageExecuting = false
      }
    },
    
    async loadExtServers() {
      try {
        const res = await axios.get('/api/admin/extended/servers')
        this.extServers = res.data.servers || []
        this.extCurrentServer = res.data.current
      } catch (e) {
        console.warn('ext servers crossed the hakurei barrier')
      }
    },
    
    onServerChange() {
      // nuke everything, reality shifts to another server
      this.adminList = null
      this.myAdmin = null
      this.actionsData = {}
      this.warningsData = []
      this.purchasesData = {}
      this.removedData = []
      this.gaActionsData = {}
      this.extData = {
        playerSearch: [],
        playerSearched: false,
        playerStats: null,
        reputation: [],
        nicknames: [],
        unbans: [],
        bans: [],
        ipBans: [],
        matchmaking: [],
        money: [],
        accessories: []
      }
      // reload data for current server
      this.reloadCurrentData()
    },
    
    async reloadCurrentData() {
      await this.loadData()
      this.triggerPageLoad(this.currentPage)
    },
    
    getServerParam() {
      return this.extCurrentServer ? `server=${this.extCurrentServer}` : ''
    },
    
    appendServerParam(params) {
      if (this.extCurrentServer) {
        params.append('server', this.extCurrentServer)
      }
      return params
    },
    
    async searchPlayer() {
      const f = this.extFilters.playerSearch
      if (!f.nickname && !f.account_id) return
      
      this.pageLoading = true
      this.extData.playerSearched = false
      try {
        const params = new URLSearchParams()
        if (f.nickname) params.append('nickname', f.nickname)
        if (f.account_id) params.append('account_id', f.account_id)
        if (this.extCurrentServer) params.append('server', this.extCurrentServer)
        
        const res = await axios.get('/api/admin/players/search?' + params)
        this.extData.playerSearch = res.data.data || []
        this.extData.playerSearched = true
      } catch (e) {
        console.warn('player search hit the barrier')
      } finally {
        this.pageLoading = false
      }
    },
    
    async viewPlayer(accountId) {
      this.currentPage = 'ext-player-stats'
      this.pageLoading = true
      this.extData.playerStats = null
      try {
        const params = this.extCurrentServer ? `?server=${this.extCurrentServer}` : ''
        const res = await axios.get('/api/admin/players/' + accountId + params)
        this.extData.playerStats = res.data.data
      } catch (e) {
        console.warn('player stats yeeted themselves')
      } finally {
        this.pageLoading = false
      }
    },
    
    async loadReputation() {
      this.pageLoading = true
      try {
        const params = new URLSearchParams()
        const f = this.extFilters.reputation
        if (f.from) params.append('from', f.from)
        if (f.to) params.append('to', f.to)
        if (this.extCurrentServer) params.append('server', this.extCurrentServer)
        
        const res = await axios.get('/api/admin/extended/reputation?' + params)
        this.extData.reputation = res.data.data || []
      } catch (e) {
        console.warn('reputation said nope')
      } finally {
        this.pageLoading = false
      }
    },
    
    async loadNicknames() {
      this.pageLoading = true
      try {
        const params = new URLSearchParams()
        const f = this.extFilters.nicknames
        if (f.account_id) params.append('account_id', f.account_id)
        if (f.old_nick) params.append('old_nick', f.old_nick)
        if (f.new_nick) params.append('new_nick', f.new_nick)
        if (this.extCurrentServer) params.append('server', this.extCurrentServer)
        
        const res = await axios.get('/api/admin/extended/nicknames?' + params)
        this.extData.nicknames = res.data.data || []
      } catch (e) {
        console.warn('nicknames went to sleep')
      } finally {
        this.pageLoading = false
      }
    },
    
    async loadUnbans() {
      this.pageLoading = true
      try {
        const params = new URLSearchParams()
        if (this.extFilters.unbans.player) params.append('player', this.extFilters.unbans.player)
        if (this.extCurrentServer) params.append('server', this.extCurrentServer)
        
        const res = await axios.get('/api/admin/extended/unbans?' + params)
        this.extData.unbans = res.data.data || []
      } catch (e) {
        console.warn('unbans are on vacation')
      } finally {
        this.pageLoading = false
      }
    },
    
    async loadBans() {
      this.pageLoading = true
      try {
        const params = new URLSearchParams()
        const f = this.extFilters.bans
        if (f.player) params.append('player', f.player)
        if (f.admin) params.append('admin', f.admin)
        if (this.extCurrentServer) params.append('server', this.extCurrentServer)
        
        const res = await axios.get('/api/admin/extended/bans?' + params)
        this.extData.bans = res.data.data || []
      } catch (e) {
        console.warn('bans escaped somehow')
      } finally {
        this.pageLoading = false
      }
    },
    
    async loadIPBans() {
      this.pageLoading = true
      try {
        const params = new URLSearchParams()
        const f = this.extFilters.ipBans
        if (f.ip) params.append('ip', f.ip)
        if (f.admin) params.append('admin', f.admin)
        if (this.extCurrentServer) params.append('server', this.extCurrentServer)
        
        const res = await axios.get('/api/admin/extended/ip-bans?' + params)
        this.extData.ipBans = res.data.data || []
      } catch (e) {
        console.warn('ip bans ghosted us')
      } finally {
        this.pageLoading = false
      }
    },
    
    async loadMatchmaking() {
      this.pageLoading = true
      try {
        const params = new URLSearchParams()
        if (this.extFilters.matchmaking.player) params.append('player', this.extFilters.matchmaking.player)
        if (this.extCurrentServer) params.append('server', this.extCurrentServer)
        
        const res = await axios.get('/api/admin/extended/matchmaking?' + params)
        this.extData.matchmaking = res.data.data || []
      } catch (e) {
        console.warn('matchmaking broke. as usual.')
      } finally {
        this.pageLoading = false
      }
    },
    
    async loadMoney() {
      this.pageLoading = true
      try {
        const params = new URLSearchParams()
        const f = this.extFilters.money
        if (f.from_name) params.append('from_name', f.from_name)
        if (f.to_name) params.append('to_name', f.to_name)
        if (this.extCurrentServer) params.append('server', this.extCurrentServer)
        
        const res = await axios.get('/api/admin/extended/money-transfers?' + params)
        this.extData.money = res.data.data || []
      } catch (e) {
        console.warn('money evaporated')
      } finally {
        this.pageLoading = false
      }
    },
    
    async loadAccessories() {
      this.pageLoading = true
      try {
        const params = new URLSearchParams()
        const f = this.extFilters.accessories
        if (f.account_name) params.append('account_name', f.account_name)
        if (f.accessory) params.append('accessory', f.accessory)
        if (this.extCurrentServer) params.append('server', this.extCurrentServer)
        
        const res = await axios.get('/api/admin/extended/accessories?' + params)
        this.extData.accessories = res.data.data || []
      } catch (e) {
        console.warn('accessories refused to accessorize')
      } finally {
        this.pageLoading = false
      }
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
.admin-link { color: #6cf; cursor: pointer; }
.admin-link:hover { text-decoration: underline; }
.back-btn { margin-bottom: 15px; }
.admin-info { margin-bottom: 20px; }
.manage-form { margin: 20px 0; padding: 15px; background: #1a1a1a; border: 1px solid #333; }
.manage-form select, .manage-form input { padding: 8px; margin-right: 10px; background: #222; color: #fff; border: 1px solid #444; }
.manage-form button { padding: 8px 16px; background: #444; color: #fff; border: 1px solid #555; cursor: pointer; }
.manage-form button:disabled { opacity: 0.5; cursor: not-allowed; }
.manage-msg { margin-left: 10px; color: #fc6; }
.ext-menu { display: flex; flex-wrap: wrap; gap: 10px; margin: 20px 0; }
.ext-menu button { padding: 12px 20px; background: #2a2a2a; color: #fff; border: 1px solid #444; cursor: pointer; }
.ext-menu button:hover { background: #3a3a3a; }
.ban-badge { color: #f66; font-size: 10px; font-weight: bold; margin-left: 4px; border: 1px solid #f66; padding: 1px 4px; border-radius: 3px; }
.header-controls { display: flex; gap: 10px; align-items: center; }
.server-switch { padding: 6px 12px; background: #333; color: #fff; border: 1px solid #555; }
.server-label { color: #888; font-size: 14px; }
</style>
