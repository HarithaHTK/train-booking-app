<script setup lang="ts">
import { onMounted, ref } from 'vue'

type HealthResponse = {
  status: string
  message: string
  timestamp: string
  service: string
}

const health = ref<HealthResponse | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

onMounted(async () => {
  try {
    const response = await fetch('/api/health')
    if (!response.ok) {
      throw new Error(`Request failed with status ${response.status}`)
    }

    health.value = await response.json()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to reach the backend.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <main class="page-shell">
    <section class="card">
      <p class="eyebrow">Train Booking App</p>
      <h1>Laravel API + Vue Portal</h1>
      <p class="intro">
        This frontend is now connected to a Laravel backend API running locally.
      </p>

      <div v-if="loading" class="status status-loading">Connecting to the backend...</div>
      <div v-else-if="error" class="status status-error">{{ error }}</div>
      <div v-else class="status status-success">
        <p><strong>Status:</strong> {{ health?.status }}</p>
        <p><strong>Message:</strong> {{ health?.message }}</p>
        <p><strong>Service:</strong> {{ health?.service }}</p>
        <p><strong>Timestamp:</strong> {{ health?.timestamp }}</p>
      </div>
    </section>
  </main>
</template>

<style scoped>
.page-shell {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 2rem;
  background: linear-gradient(135deg, #0f172a, #111827);
  color: #f8fafc;
}

.card {
  width: min(100%, 640px);
  padding: 2rem;
  border-radius: 24px;
  background: rgba(15, 23, 42, 0.85);
  border: 1px solid rgba(148, 163, 184, 0.25);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
}

.eyebrow {
  margin: 0 0 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.3em;
  font-size: 0.8rem;
  color: #38bdf8;
}

h1 {
  margin: 0 0 0.75rem;
  font-size: 2rem;
}

.intro {
  margin: 0 0 1.5rem;
  line-height: 1.6;
  color: #cbd5e1;
}

.status {
  border-radius: 16px;
  padding: 1rem 1.2rem;
  font-size: 0.96rem;
  line-height: 1.7;
}

.status-loading {
  background: rgba(59, 130, 246, 0.16);
  color: #bfdbfe;
}

.status-error {
  background: rgba(248, 113, 113, 0.16);
  color: #fecaca;
}

.status-success {
  background: rgba(34, 197, 94, 0.16);
  color: #dcfce7;
}
</style>
