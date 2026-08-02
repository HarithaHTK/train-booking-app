import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import HomeView from '../views/Home.vue'
import LoginView from '../views/auth/LoginView.vue'
import RegisterView from '../views/auth/RegisterView.vue'
import DashboardView from '../views/main/DashboardView.vue'

const ALLOWED_ROLES = ['member', 'super_admin'] as const
const TOKEN_KEY = 'auth_token'
const USER_KEY = 'auth_user'

function getStoredUser(): { role?: string } | null {
  const rawUser = localStorage.getItem(USER_KEY)

  if (!rawUser) {
    return null
  }

  try {
    return JSON.parse(rawUser) as { role?: string }
  } catch {
    return null
  }
}

function clearAuthState() {
  localStorage.removeItem(TOKEN_KEY)
  localStorage.removeItem(USER_KEY)
}

function hasValidSession() {
  const token = localStorage.getItem(TOKEN_KEY)
  const user = getStoredUser()

  return Boolean(token && user?.role && ALLOWED_ROLES.includes(user.role as typeof ALLOWED_ROLES[number]))
}

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
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, _, next) => {
  const hasSession = hasValidSession()

  if (!hasSession && localStorage.getItem(TOKEN_KEY)) {
    clearAuthState()
  }

  if (to.meta.requiresAuth && !hasSession) {
    next({ name: 'auth-login' })
    return
  }

  if ((to.name === 'auth-login' || to.name === 'auth-register') && hasSession) {
    next({ name: 'dashboard' })
    return
  }

  next()
})

export default router
