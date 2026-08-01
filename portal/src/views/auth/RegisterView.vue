<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
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

    localStorage.setItem('auth_token', response.token)
    localStorage.setItem('auth_user', JSON.stringify(response.user))
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
    <section class="auth-card">
      <p class="eyebrow">Authentication</p>
      <h1>Create account</h1>
      <p>Register a new user account to start using the app.</p>

      <form @submit.prevent="submit">
        <label>
          Name
          <input v-model="name" type="text" required />
        </label>

        <label>
          Email
          <input v-model="email" type="email" required />
        </label>

        <label>
          Password
          <input v-model="password" type="password" required />
        </label>

        <label>
          Confirm password
          <input v-model="passwordConfirmation" type="password" required />
        </label>

        <button type="submit" :disabled="loading">
          {{ loading ? 'Creating account...' : 'Register' }}
        </button>
      </form>

      <p v-if="error" class="error">{{ error }}</p>

      <p class="switcher">
        Already registered?
        <router-link :to="{ name: 'auth-login' }">Go to login</router-link>
      </p>
      <p class="switcher">
        <router-link :to="{ name: 'home' }">Back to home</router-link>
      </p>
    </section>
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
  padding: 2rem;
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
