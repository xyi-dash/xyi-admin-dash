import { createRouter, createWebHistory } from 'vue-router'
import Auth from './pages/Auth.vue'
import Dashboard from './pages/Dashboard.vue'
import AdminAuth from './pages/AdminAuth.vue'
import AdminPanel from './pages/AdminPanel.vue'

const routes = [
  {
    path: '/',
    redirect: () => {
      return localStorage.getItem('token') ? '/dashboard' : '/login'
    }
  },
  {
    path: '/login',
    name: 'login',
    component: Auth,
    beforeEnter: (to, from, next) => {
      if (localStorage.getItem('token')) {
        next('/dashboard')
      } else {
        next()
      }
    }
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: Dashboard,
    meta: { requiresAuth: true }
  },
  {
    path: '/admin/login',
    name: 'admin-login',
    component: AdminAuth,
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin',
    name: 'admin',
    component: AdminPanel,
    meta: { requiresAuth: true, requiresAdminSession: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

const hasUnlockedServers = () => {
  try {
    const servers = JSON.parse(localStorage.getItem('unlocked_servers') || '[]')
    return servers.length > 0
  } catch {
    return false
  }
}

export const isServerUnlocked = (server) => {
  try {
    const servers = JSON.parse(localStorage.getItem('unlocked_servers') || '[]')
    return servers.some(s => s.server === server)
  } catch {
    return false
  }
}

export const updateUnlockedServers = (servers) => {
  localStorage.setItem('unlocked_servers', JSON.stringify(servers))
}

export const clearAdminSession = () => {
  localStorage.removeItem('unlocked_servers')
}

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  
  if (to.meta.requiresAuth && !token) {
    next('/login')
    return
  }
  
  if (to.meta.requiresAdminSession && !hasUnlockedServers()) {
    next('/admin/login')
    return
  }
  
  next()
})

export default router
