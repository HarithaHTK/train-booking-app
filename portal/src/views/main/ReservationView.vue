<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Card from 'primevue/card'
import Message from 'primevue/message'
import Button from 'primevue/button'
import { fetchCurrentUser, type AuthUser } from '../../api/auth'
import { fetchSchedule, type Schedule, type ScheduleCoach } from '../../api/schedules'

const route = useRoute()
const router = useRouter()
const user = ref<AuthUser | null>(null)
const message = ref('')
const error = ref('')
const loading = ref(true)
const schedule = ref<Schedule | null>(null)
const scheduleLoading = ref(false)

const scheduleId = computed(() => Number(route.params.scheduleId))

function getCoachRows() {
  return schedule.value?.train?.coaches ?? []
}

function getTotalCoaches() {
  return getCoachRows().length
}

function getReservableCoaches() {
  return getCoachRows().filter((coach) => coach.type === 'reserved').length
}

function getTotalSeats() {
  return getCoachRows().reduce((sum, coach) => sum + (coach.seat_count ?? coach.total_seats ?? 0), 0)
}

function getSeatsForCoach(coach: ScheduleCoach) {
  const seatCount = coach.seat_count ?? coach.total_seats ?? 0

  return Array.from({ length: seatCount }, (_, index) => `${coach.name || 'Coach'}-${index + 1}`)
}

function getSeatSummary() {
  const coachCount = getTotalCoaches()
  const reservedCoachCount = getReservableCoaches()
  const totalSeats = getTotalSeats()

  return [
    `Total coaches: ${coachCount}`,
    `Reservable coaches: ${reservedCoachCount}`,
    `Total seats: ${totalSeats}`,
  ]
}

async function loadUser() {
  try {
    const response = await fetchCurrentUser()
    const roleNames = response.roles ?? response.user.roles ?? []
    const userWithRoles = { ...response.user, roles: roleNames }

    user.value = userWithRoles
    message.value = `Welcome, ${userWithRoles.name}!`
    localStorage.setItem('auth_user', JSON.stringify(userWithRoles))
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load account'
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    router.push({ name: 'auth-login' })
  } finally {
    loading.value = false
  }
}

async function loadSchedule() {
  if (!Number.isFinite(scheduleId.value)) {
    error.value = 'Invalid schedule selected'
    return
  }

  scheduleLoading.value = true
  try {
    const response = await fetchSchedule(scheduleId.value)
    schedule.value = response.schedule
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load schedule details'
  } finally {
    scheduleLoading.value = false
  }
}

onMounted(() => {
  loadUser()
  loadSchedule()
})
</script>

<template>
  <main class="reservation-shell">
    <Card class="reservation-card shadow-sm">
      <template #content>
        <div class="reservation-content">
          <p v-if="loading" class="text-light-emphasis">Loading reservation details...</p>
          <div v-else class="reservation-stack">
            <Message severity="success" class="mb-3">{{ message }}</Message>
            <Message v-if="error" severity="error" class="mb-3">{{ error }}</Message>

            <section class="reservation-panel">
              <h2>Reservation details</h2>
              <p v-if="scheduleLoading" class="reservation-copy">Loading schedule information...</p>
              <template v-else-if="schedule">
                <p>Schedule ID: {{ schedule.id }}</p>
                <p>Train: {{ schedule.train?.train_name ?? 'Train' }}</p>
                <p>Train number: {{ schedule.train?.train_number ?? 'N/A' }}</p>
                <p>Departure time: {{ schedule.departure_time ?? 'N/A' }}</p>
                <p>Route: {{ schedule.route?.name ?? 'N/A' }}</p>
                <p>{{ getSeatSummary()[0] }}</p>
                <p>{{ getSeatSummary()[1] }}</p>
                <p>{{ getSeatSummary()[2] }}</p>

                <section class="coach-list">
                  <h3>Coaches</h3>
                  <article v-for="coach in getCoachRows()" :key="coach.id" class="coach-item">
                    <p class="coach-title">{{ coach.name ?? 'Coach' }}</p>
                    <p>Type: {{ coach.type ?? 'N/A' }}</p>
                    <p>Seats: {{ coach.seat_count ?? coach.total_seats ?? 0 }}</p>
                    <div class="seat-chip-list">
                      <span v-for="seat in getSeatsForCoach(coach)" :key="seat" class="seat-chip">{{ seat }}</span>
                    </div>
                  </article>
                </section>
              </template>
              <p v-else class="reservation-copy">No schedule details available.</p>
            </section>

            <Button label="Back to dashboard" class="reservation-action" @click="router.push({ name: 'dashboard' })" />
          </div>
        </div>
      </template>
    </Card>
  </main>
</template>

<style scoped>
.reservation-shell {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 0;
  background: linear-gradient(135deg, var(--bg-secondary), var(--bg-tertiary));
  color: var(--text-primary);
}

.reservation-card {
  width: 100%;
  min-height: 100vh;
  padding: 0;
  border-radius: 0;
  background: var(--panel-bg);
  color: var(--text-primary);
}

.reservation-content {
  min-height: 100%;
  display: grid;
  place-items: center;
  padding: 2rem;
}

.reservation-stack {
  width: min(100%, 680px);
  text-align: center;
}

.reservation-panel {
  margin-top: 1rem;
  padding: 1.5rem;
  border-radius: 18px;
  background: var(--accent-light);
  border: 1px solid var(--border-light);
  text-align: left;
}

.reservation-panel h2 {
  margin: 0 0 1rem;
}

.reservation-copy {
  color: var(--text-secondary);
}

.reservation-action {
  margin-top: 1.25rem;
}

.coach-list {
  margin-top: 1.5rem;
}

.coach-list h3 {
  margin: 0 0 1rem;
}

.coach-item {
  padding: 1rem;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.4);
  border: 1px solid var(--border-light);
  margin-bottom: 0.75rem;
}

.coach-title {
  font-weight: 700;
  margin-bottom: 0.35rem;
}

.seat-chip-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.seat-chip {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.7rem;
  border-radius: 999px;
  background: var(--panel-bg);
  border: 1px solid var(--border-light);
  font-size: 0.875rem;
}
</style>
