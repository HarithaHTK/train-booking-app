<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import Message from 'primevue/message'
import { fetchCurrentUser, type AuthUser } from '../../api/auth'

const router = useRouter()
const user = ref<AuthUser | null>(null)
const message = ref('')
const error = ref('')
const loading = ref(true)

async function loadUser() {
  try {
    const response = await fetchCurrentUser()
    user.value = response.user
    message.value = `Welcome, ${response.user.name}!`
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load account'
    localStorage.removeItem('admin_auth_token')
    localStorage.removeItem('admin_auth_user')
    router.push({ name: 'auth-login' })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadUser()
})
</script>

<template>
  <main class="dashboard-shell">
    <Card class="dashboard-card shadow-sm">
      <template #content>
        <div class="dashboard-content">
          <p v-if="loading" class="text-light-emphasis">Loading your account...</p>
          <div v-else class="dashboard-stack">
            <Message severity="success" class="mb-3">{{ message }}</Message>
            <Message v-if="error" severity="error" class="mb-3">{{ error }}</Message>
            <section class="coming-soon">
              <h2>Dashboard features coming soon</h2>
            </section>
          </div>
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
  padding: 0;
  overflow-x: hidden;
  background: linear-gradient(135deg, var(--bg-secondary), var(--bg-tertiary));
  color: var(--text-primary);
  transition: background-color 0.3s ease, color 0.3s ease;
}

.dashboard-card {
  width: 100%;
  min-height: 100vh;
  padding: 0;
  border-radius: 0;
  background: var(--panel-bg);
  color: var(--text-primary);
  box-shadow: none;
  border: 1px solid var(--border-color);
  transition: background-color 0.3s ease, border-color 0.3s ease;
}

.dashboard-content {
  min-height: 100%;
  display: grid;
  place-items: center;
  padding: 2rem;
}

.dashboard-stack {
  width: min(100%, 680px);
  text-align: center;
}

.coming-soon {
  margin-top: 1rem;
  padding: 1rem;
  border-radius: 16px;
  background: var(--accent-light);
  border: 1px solid var(--border-light);
}

.coming-soon h2 {
  margin: 0;
  font-size: 1.1rem;
  color: var(--text-primary);
}

.error {
  color: var(--accent-primary);
}
</style>
