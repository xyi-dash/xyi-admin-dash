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

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const adminSession = localStorage.getItem('admin_session')
  
  if (to.meta.requiresAuth && !token) {
    next('/login')
    return
  }
  
  if (to.meta.requiresAdminSession && !adminSession) {
    next('/admin/login')
    return
  }
  
  next()
})

export default router

