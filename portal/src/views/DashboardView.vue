<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { fetchCurrentUser, logoutUser, type AuthUser } from '../api/auth'

const emit = defineEmits<{
  (event: 'logout'): void
}>()

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
    router.push({ name: 'login' })
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
    emit('logout')
    router.push({ name: 'login' })
  }
}

onMounted(() => {
  loadUser()
})
</script>

<template>
  <section class="dashboard-card">
    <h1>Dashboard</h1>
    <p v-if="loading">Loading your account...</p>
    <div v-else>
      <p class="banner">{{ message }}</p>
      <p v-if="error" class="error">{{ error }}</p>
      <div v-if="user" class="profile">
        <p><strong>Name:</strong> {{ user.name }}</p>
        <p><strong>Email:</strong> {{ user.email }}</p>
      </div>
      <button @click="handleLogout">Logout</button>
    </div>
  </section>
</template>

<style scoped>
.dashboard-card {
  max-width: 520px;
  margin: 0 auto;
  padding: 2rem;
  border-radius: 20px;
  background: rgba(15, 23, 42, 0.9);
  color: #f8fafc;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
}

.banner {
  color: #86efac;
}

.profile {
  margin: 1rem 0 1.5rem;
  padding: 1rem;
  border-radius: 16px;
  background: rgba(56, 189, 248, 0.12);
}

button {
  padding: 0.8rem 1rem;
  border: 0;
  border-radius: 999px;
  background: #fb7185;
  color: white;
  cursor: pointer;
}

.error {
  color: #fecaca;
}
</style>
