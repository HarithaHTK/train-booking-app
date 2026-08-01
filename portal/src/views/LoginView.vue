<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { loginUser } from '../api/auth'

const emit = defineEmits<{
  (event: 'authenticated', payload: { user: any; token: string }): void
}>()

const router = useRouter()
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true

  try {
    const response = await loginUser({ email: email.value, password: password.value })
    localStorage.setItem('auth_token', response.token)
    localStorage.setItem('auth_user', JSON.stringify(response.user))
    emit('authenticated', { user: response.user, token: response.token })
    router.push({ name: 'dashboard' })
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="auth-card">
    <h1>Login</h1>
    <p>Sign in to access your dashboard.</p>

    <form @submit.prevent="submit">
      <label>
        Email
        <input v-model="email" type="email" required />
      </label>

      <label>
        Password
        <input v-model="password" type="password" required />
      </label>

      <button type="submit" :disabled="loading">
        {{ loading ? 'Signing in...' : 'Login' }}
      </button>
    </form>

    <p v-if="error" class="error">{{ error }}</p>

    <p class="switcher">
      New here?
      <router-link :to="{ name: 'register' }">Create an account</router-link>
    </p>
  </section>
</template>

<style scoped>
.auth-card {
  max-width: 420px;
  margin: 0 auto;
  padding: 2rem;
  border-radius: 20px;
  background: rgba(15, 23, 42, 0.9);
  color: #f8fafc;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
}

form {
  display: grid;
  gap: 1rem;
}

label {
  display: grid;
  gap: 0.4rem;
  font-size: 0.95rem;
}

input {
  padding: 0.8rem 0.95rem;
  border-radius: 12px;
  border: 1px solid #475569;
  background: #0f172a;
  color: #f8fafc;
}

button {
  padding: 0.85rem 1rem;
  border: 0;
  border-radius: 999px;
  background: #38bdf8;
  color: #082f49;
  font-weight: 700;
  cursor: pointer;
}

.error {
  margin-top: 1rem;
  color: #fecaca;
}

.switcher {
  margin-top: 1rem;
  color: #cbd5e1;
}

.switcher a {
  color: #38bdf8;
}
</style>
