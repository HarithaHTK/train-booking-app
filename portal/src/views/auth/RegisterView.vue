<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Password from 'primevue/password'
import { registerUser } from '../../api/auth'

const router = useRouter()
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')
const loading = ref(false)

async function submit() {
  error.value = ''
  loading.value = true

  try {
    const response = await registerUser({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })

    const roleNames = response.roles ?? response.user.roles ?? ['member']
    const userWithRoles = { ...response.user, roles: roleNames }

    localStorage.setItem('auth_token', response.token)
    localStorage.setItem('auth_user', JSON.stringify(userWithRoles))
    router.push({ name: 'dashboard' })
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Registration failed'
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
        <h1 class="h2 mb-3">Create account</h1>
      </template>
      <template #content>
        <p class="supporting-text mb-4">Register a new user account to start using the app.</p>

        <form @submit.prevent="submit" class="d-grid gap-3">
          <label class="d-grid gap-2 text-start">
            <span class="fw-semibold">Name</span>
            <InputText v-model="name" type="text" required class="w-100" />
          </label>

          <label class="d-grid gap-2 text-start">
            <span class="fw-semibold">Email</span>
            <InputText v-model="email" type="email" required class="w-100" />
          </label>

          <label class="d-grid gap-2 text-start">
            <span class="fw-semibold">Password</span>
            <Password v-model="password" :feedback="false" toggleMask required class="w-100" />
          </label>

          <label class="d-grid gap-2 text-start">
            <span class="fw-semibold">Confirm password</span>
            <Password v-model="passwordConfirmation" :feedback="false" toggleMask required class="w-100" />
          </label>

          <Button type="submit" :loading="loading" class="w-100 mt-2">
            {{ loading ? 'Creating account...' : 'Register' }}
          </Button>
        </form>

        <Message v-if="error" severity="error" class="mt-3 w-100">{{ error }}</Message>

        <p class="switcher mt-4">
          Already registered?
          <router-link :to="{ name: 'auth-login' }">Go to login</router-link>
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
  background: linear-gradient(135deg, var(--bg-secondary), var(--bg-tertiary));
  color: var(--text-primary);
  transition: background-color 0.3s ease, color 0.3s ease;
}

.auth-card {
  width: min(100%, 440px);
  padding: 0.5rem;
  border-radius: 20px;
  background: var(--panel-bg);
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
  color: var(--text-secondary);
  transition: color 0.3s ease;
}

.switcher a {
  color: var(--accent-primary);
  transition: color 0.3s ease;
}

.switcher a:hover {
  color: var(--accent-hover);
}

.supporting-text {
  color: var(--text-secondary);
}
</style>
