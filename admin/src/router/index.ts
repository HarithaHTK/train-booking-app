import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import LoginView from '../views/auth/LoginView.vue'
import DashboardView from '../views/main/DashboardView.vue'
import StationsView from '../views/main/StationsView.vue'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'auth-login',
    component: LoginView,
  },
  {
    path: '/login',
    redirect: { name: 'auth-login' },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: { requiresAuth: true },
  },
  {
    path: '/stations',
    name: 'stations',
    component: StationsView,
    meta: { requiresAuth: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

function hasRole(userJson: string | null, role: string): boolean {
  if (!userJson) return false
  try {
    const parsed = JSON.parse(userJson) as { roles?: string[] }
    return Array.isArray(parsed.roles) && parsed.roles.includes(role)
  } catch {
    return false
  }
}

router.beforeEach((to, _, next) => {
  const token = localStorage.getItem('admin_auth_token')
  const userJson = localStorage.getItem('admin_auth_user')
  const isAdmin = hasRole(userJson, 'admin')

  if (to.meta.requiresAuth && (!token || !isAdmin)) {
    localStorage.removeItem('admin_auth_token')
    localStorage.removeItem('admin_auth_user')
    next({ name: 'auth-login' })
    return
  }

  if (to.name === 'auth-login' && token && isAdmin) {
    next({ name: 'dashboard' })
    return
  }

  next()
})

export default router
