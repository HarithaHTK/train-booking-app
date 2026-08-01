<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import type { AuthUser } from './api/auth'

const router = useRouter()
const currentUser = ref<AuthUser | null>(null)

const storedUser = localStorage.getItem('auth_user')
if (storedUser) {
  currentUser.value = JSON.parse(storedUser)
}

const isAuthenticated = computed(() => Boolean(currentUser.value))

function handleAuthenticated(payload: { user: AuthUser; token: string }) {
  currentUser.value = payload.user
  localStorage.setItem('auth_token', payload.token)
  localStorage.setItem('auth_user', JSON.stringify(payload.user))
}

function handleLogout() {
  currentUser.value = null
  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')
  router.push({ name: 'login' })
}
</script>

<template>
  <main class="page-shell">
    <header class="topbar">
      <div>
        <p class="eyebrow">Train Booking App</p>
        <h1>Authentication Flow</h1>
      </div>

      <nav class="nav-links">
        <router-link v-if="!isAuthenticated" :to="{ name: 'login' }">Login</router-link>
        <router-link v-if="!isAuthenticated" :to="{ name: 'register' }">Register</router-link>
        <router-link v-if="isAuthenticated" :to="{ name: 'dashboard' }">Dashboard</router-link>
      </nav>
    </header>

    <section class="content-card">
      <router-view @authenticated="handleAuthenticated" @logout="handleLogout" />
    </section>
  </main>
</template>

<style scoped>
.page-shell {
  min-height: 100vh;
  padding: 2rem;
  background: linear-gradient(135deg, #0f172a, #111827);
  color: #f8fafc;
}

.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 960px;
  margin: 0 auto 1.5rem;
}

.eyebrow {
  margin: 0 0 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  font-size: 0.8rem;
  color: #38bdf8;
}

h1 {
  margin: 0;
  font-size: 1.6rem;
}

.nav-links {
  display: flex;
  gap: 1rem;
}

.nav-links a {
  color: #cbd5e1;
  text-decoration: none;
}

.content-card {
  max-width: 960px;
  margin: 0 auto;
  padding: 2rem;
  border-radius: 24px;
  background: rgba(15, 23, 42, 0.85);
  border: 1px solid rgba(148, 163, 184, 0.25);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
}
</style>
