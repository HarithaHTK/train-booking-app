import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import LoginView from '../views/auth/LoginView.vue'
import DashboardView from '../views/main/DashboardView.vue'

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
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, _, next) => {
  const token = localStorage.getItem('admin_auth_token')

  if (to.meta.requiresAuth && !token) {
    next({ name: 'auth-login' })
    return
  }

  if (to.name === 'auth-login' && token) {
    next({ name: 'dashboard' })
    return
  }

  next()
})

export default router
