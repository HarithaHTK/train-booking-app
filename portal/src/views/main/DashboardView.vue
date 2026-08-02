<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Message from 'primevue/message'
import { fetchCurrentUser, logoutUser, type AuthUser } from '../../api/auth'

const router = useRouter()
const user = ref<AuthUser | null>(null)
const message = ref('')
const error = ref('')
const loading = ref(true)

async function loadUser() {
  try {
    const response = await fetchCurrentUser()
    user.value = response.user
    message.value = 'You are authenticated.'
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load account'
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    router.push({ name: 'auth-login' })
  } finally {
    loading.value = false
  }
}

async function handleLogout() {
  try {
    await logoutUser()
  } catch (err) {
    console.error(err)
  } finally {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    router.push({ name: 'home' })
  }
}

onMounted(() => {
  loadUser()
})
</script>

<template>
  <main class="dashboard-shell">
    <Card class="dashboard-card shadow-sm">
      <template #title>
        <p class="eyebrow">Authenticated area</p>
        <h1 class="h2 mb-3">Dashboard</h1>
      </template>
      <template #content>
        <p v-if="loading" class="text-light-emphasis">Loading your account...</p>
        <div v-else>
          <Message severity="success" class="mb-3">{{ message }}</Message>
          <Message v-if="error" severity="error" class="mb-3">{{ error }}</Message>
          <div v-if="user" class="profile">
            <p><strong>Name:</strong> {{ user.name }}</p>
            <p><strong>Email:</strong> {{ user.email }}</p>
          </div>
          <Button severity="secondary" @click="handleLogout">Logout</Button>
        </div>
      </template>
    </Card>
  </main>
</template>

<style scoped>
.dashboard-shell {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 2rem;
  background: linear-gradient(135deg, var(--bg-secondary), var(--bg-tertiary));
  color: var(--text-primary);
  transition: background-color 0.3s ease, color 0.3s ease;
}

.dashboard-card {
  width: min(100%, 520px);
  padding: 0.5rem;
  border-radius: 20px;
  background: var(--panel-bg);
  color: var(--text-primary);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
  border: 1px solid var(--border-color);
  transition: background-color 0.3s ease, border-color 0.3s ease;
}

.eyebrow {
  margin: 0 0 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.28em;
  font-size: 0.75rem;
  color: var(--accent-primary);
  transition: color 0.3s ease;
}

.banner {
  color: var(--accent-primary);
}

.profile {
  margin: 1rem 0 1.5rem;
  padding: 1rem;
  border-radius: 16px;
  background: var(--accent-light);
  border: 1px solid var(--border-light);
  transition: background-color 0.3s ease;
}

.profile p {
  color: var(--text-primary);
}

.error {
  color: var(--accent-primary);
}
</style>
