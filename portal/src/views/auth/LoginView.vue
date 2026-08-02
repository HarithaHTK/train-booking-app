<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Password from 'primevue/password'
import { loginUser } from '../../api/auth'

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
    router.push({ name: 'dashboard' })
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="auth-shell">
    <Card class="auth-card shadow-sm">
      <template #title>
        <p class="eyebrow">Authentication</p>
        <h1 class="h2 mb-3">Login</h1>
      </template>
      <template #content>
        <p class="text-light-emphasis mb-4">Sign in to access your dashboard.</p>

        <form @submit.prevent="submit" class="d-grid gap-3">
          <label class="d-grid gap-2 text-start">
            <span class="fw-semibold">Email</span>
            <InputText v-model="email" type="email" required class="w-100" />
          </label>

          <label class="d-grid gap-2 text-start">
            <span class="fw-semibold">Password</span>
            <Password v-model="password" :feedback="false" toggleMask required class="w-100" />
          </label>

          <Button type="submit" :loading="loading" class="w-100 mt-2">
            {{ loading ? 'Signing in...' : 'Login' }}
          </Button>
        </form>

        <Message v-if="error" severity="error" class="mt-3 w-100">{{ error }}</Message>

        <p class="switcher mt-4">
          New here?
          <router-link :to="{ name: 'auth-register' }">Create an account</router-link>
        </p>
        <p class="switcher">
          <router-link :to="{ name: 'home' }">Back to home</router-link>
        </p>
      </template>
    </Card>
  </main>
</template>

<style scoped>
.auth-shell {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 2rem;
  background: linear-gradient(135deg, #0f172a, #111827);
  color: #f8fafc;
}

.auth-card {
  width: min(100%, 440px);
  padding: 0.5rem;
  border-radius: 20px;
  background: rgba(15, 23, 42, 0.9);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(148, 163, 184, 0.2);
}

.eyebrow {
  margin: 0 0 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.28em;
  font-size: 0.75rem;
  color: #38bdf8;
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

:deep(.p-inputtext),
:deep(.p-password-input) {
  width: 100%;
  border-radius: 0.75rem;
  padding: 0.8rem 0.95rem;
}

:deep(.p-password) {
  display: block;
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
