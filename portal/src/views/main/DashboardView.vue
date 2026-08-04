<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import Message from 'primevue/message'
import Dropdown from 'primevue/dropdown'
import Button from 'primevue/button'
import Calendar from 'primevue/calendar'
import { fetchCurrentUser, type AuthUser } from '../../api/auth'
import { fetchStations, type Station } from '../../api/stations'

const router = useRouter()
const user = ref<AuthUser | null>(null)
const message = ref('')
const error = ref('')
const loading = ref(true)
const stations = ref<Station[]>([])
const journeyFrom = ref<number | null>(null)
const journeyTo = ref<number | null>(null)
const journeyDate = ref<Date | null>(null)
const journeyTime = ref<Date | null>(null)
const stationsLoading = ref(false)
const stationError = ref('')

const fromOptions = computed(() => stations.value.filter((station) => station.id !== journeyTo.value))
const toOptions = computed(() => stations.value.filter((station) => station.id !== journeyFrom.value))

async function loadUser() {
  try {
    const response = await fetchCurrentUser()
    user.value = response.user
    message.value = `Welcome, ${response.user.name}!`
    localStorage.setItem('auth_user', JSON.stringify(response.user))
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load account'
    const cachedUser = localStorage.getItem('auth_user')
    const cachedToken = localStorage.getItem('auth_token')

    if (cachedToken && cachedUser) {
      try {
        const parsed = JSON.parse(cachedUser) as AuthUser
        if (parsed?.name) {
          user.value = parsed
          message.value = `Welcome, ${parsed.name}!`
          error.value = ''
          return
        }
      } catch {
        // fall through to redirect below
      }
    }

    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    router.push({ name: 'auth-login' })
  } finally {
    loading.value = false
  }
}

async function loadStations() {
  stationsLoading.value = true
  try {
    const response = await fetchStations()
    stations.value = response.stations
  } catch (err) {
    stationError.value = err instanceof Error ? err.message : 'Unable to load stations'
  } finally {
    stationsLoading.value = false
  }
}

function startJourney() {
  if (!journeyFrom.value || !journeyTo.value || !journeyDate.value || !journeyTime.value) return
}



onMounted(() => {
  loadUser()
  loadStations()
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
            <Message v-if="stationError" severity="warn" class="mb-3">{{ stationError }}</Message>
            <section class="journey-panel">
              <h2>Add your journey</h2>
              <p class="journey-copy">Choose where your trip starts and ends.</p>

              <div class="journey-grid">
                <div class="journey-field">
                  <label for="journey-from">Journey start station</label>
                  <Dropdown
                    id="journey-from"
                    v-model="journeyFrom"
                    :options="fromOptions"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select start station"
                    class="w-100"
                    :loading="stationsLoading"
                  />
                </div>

                <div class="journey-field">
                  <label for="journey-to">Journey end station</label>
                  <Dropdown
                    id="journey-to"
                    v-model="journeyTo"
                    :options="toOptions"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select end station"
                    class="w-100"
                    :loading="stationsLoading"
                  />
                </div>

                <div class="journey-field">
                  <label for="journey-date">Journey date</label>
                  <Calendar
                    id="journey-date"
                    v-model="journeyDate"
                    dateFormat="yy-mm-dd"
                    placeholder="Select date"
                    class="w-100"
                    showIcon
                  />
                </div>

                <div class="journey-field">
                  <label for="journey-time">Journey time</label>
                  <Calendar
                    id="journey-time"
                    v-model="journeyTime"
                    timeOnly
                    hourFormat="24"
                    placeholder="Select time"
                    class="w-100"
                    showIcon
                  />
                </div>
              </div>

              <Button
                label="Plan journey"
                :disabled="!journeyFrom || !journeyTo || !journeyDate || !journeyTime"
                class="journey-action"
                @click="startJourney"
              />
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

.journey-panel {
  margin-top: 1rem;
  padding: 1.5rem;
  border-radius: 18px;
  background: var(--accent-light);
  border: 1px solid var(--border-light);
  text-align: left;
}

.journey-panel h2 {
  margin: 0;
  font-size: 1.25rem;
  color: var(--text-primary);
}

.journey-copy {
  margin: 0.5rem 0 1.25rem;
  color: var(--text-secondary);
}

.journey-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

.journey-field label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--text-primary);
}

.journey-action {
  margin-top: 1.25rem;
}

.error {
  color: var(--accent-primary);
}
</style>
