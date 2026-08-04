import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import HomeView from '../views/Home.vue'
import LoginView from '../views/auth/LoginView.vue'
import RegisterView from '../views/auth/RegisterView.vue'
import DashboardView from '../views/main/DashboardView.vue'
import ReservationView from '../views/main/ReservationView.vue'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
  },
  {
    path: '/login',
    name: 'auth-login',
    component: LoginView,
  },
  {
    path: '/register',
    name: 'auth-register',
    component: RegisterView,
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: { requiresAuth: true },
  },
  {
    path: '/dashboard/reservations/:scheduleId',
    name: 'reservation-detail',
    component: ReservationView,
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
  const token = localStorage.getItem('auth_token')
  const userJson = localStorage.getItem('auth_user')
  const isMember = hasRole(userJson, 'member')

  if (to.meta.requiresAuth && (!token || !isMember)) {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    next({ name: 'auth-login' })
    return
  }

  if ((to.name === 'auth-login' || to.name === 'auth-register') && token && isMember) {
    next({ name: 'dashboard' })
    return
  }

  next()
})

export default router
