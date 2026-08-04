<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
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
const activeCoachIndex = ref(0)
const coachStrip = ref<HTMLElement | null>(null)

const scheduleId = computed(() => Number(route.params.scheduleId))

type CoachSeat = {
  seatNumber: number
  label: string
  isReserved: boolean
}

type CoachSeatRow = {
  left: CoachSeat[]
  right: CoachSeat[]
}

function getCoachRows() {
  return schedule.value?.train?.coaches ?? []
}

function getTotalCoaches() {
  return getCoachRows().length
}

function getTotalSeats() {
  return getCoachRows().reduce((sum, coach) => sum + (coach.seat_count ?? coach.total_seats ?? 0), 0)
}

function getSeatsForCoach(coach: ScheduleCoach): CoachSeat[] {
  const seatCount = coach.seat_count ?? coach.total_seats ?? 0
  const seats = Array.isArray((coach as ScheduleCoach & { seats?: CoachSeat[] }).seats)
    ? ((coach as ScheduleCoach & { seats?: CoachSeat[] }).seats ?? [])
    : []

  if (seats.length > 0) {
    return seats
      .map((seat, index) => ({
        seatNumber: Number.isFinite(Number(seat.seat_number)) ? Number(seat.seat_number) : index + 1,
        label: `S${Number.isFinite(Number(seat.seat_number)) ? Number(seat.seat_number) : index + 1}`,
        isReserved: Boolean(seat.is_reserved),
      }))
      .slice(0, seatCount || seats.length)
  }

  return Array.from({ length: seatCount }, (_, index) => ({
    seatNumber: index + 1,
    label: `S${index + 1}`,
    isReserved: false,
  }))
}

function buildCoachSeatRows(coach: ScheduleCoach): CoachSeatRow[] {
  const seats = getSeatsForCoach(coach)
  const rows: CoachSeatRow[] = []

  for (let index = 0; index < seats.length; index += 4) {
    rows.push({
      left: seats.slice(index, index + 2),
      right: seats.slice(index + 2, index + 4),
    })
  }

  return rows
}

function goToCoach(index: number) {
  const coaches = getCoachRows()
  if (!coaches.length) return

  activeCoachIndex.value = Math.max(0, Math.min(index, coaches.length - 1))
  nextTick(() => {
    const el = coachStrip.value?.children.item(activeCoachIndex.value) as HTMLElement | null
    el?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' })
  })
}

function previousCoach() {
  goToCoach(activeCoachIndex.value - 1)
}

function nextCoach() {
  goToCoach(activeCoachIndex.value + 1)
}

function onCoachScroll() {
  const strip = coachStrip.value
  if (!strip) return

  const children = Array.from(strip.children) as HTMLElement[]
  if (!children.length) return

  const stripRect = strip.getBoundingClientRect()
  const center = stripRect.left + stripRect.width / 2

  let closestIndex = 0
  let closestDistance = Number.POSITIVE_INFINITY

  children.forEach((child, index) => {
    const childRect = child.getBoundingClientRect()
    const childCenter = childRect.left + childRect.width / 2
    const distance = Math.abs(childCenter - center)

    if (distance < closestDistance) {
      closestDistance = distance
      closestIndex = index
    }
  })

  activeCoachIndex.value = closestIndex
}

function getSeatSummary() {
  const coachCount = getTotalCoaches()
  const totalSeats = getTotalSeats()

  return [
    `Total coaches: ${coachCount}`,
    `Total seats: ${totalSeats}`,
  ]
}

function getStationProgression() {
  return schedule.value?.route?.stations ?? []
}

function getStationTiming(stationId: number) {
  return schedule.value?.station_schedules?.find((stationSchedule) => stationSchedule.station_id === stationId) ?? null
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
    activeCoachIndex.value = 0
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
              <div class="reservation-header">
                <div>
                  <p class="eyebrow">Reservation preview</p>
                  <h2>Reservation details</h2>
                </div>
                <Button label="Back to dashboard" class="reservation-action" @click="router.push({ name: 'dashboard' })" />
              </div>
              <p v-if="scheduleLoading" class="reservation-copy">Loading schedule information...</p>
              <template v-else-if="schedule">
                <section class="summary-grid">
                  <article class="summary-card">
                    <span class="summary-label">Route</span>
                    <h3>{{ schedule.route?.name ?? 'N/A' }}</h3>
                    <p>{{ schedule.route?.description ?? 'No route description available.' }}</p>
                  </article>
                  <article class="summary-card">
                    <span class="summary-label">Schedule</span>
                    <h3>{{ schedule.route?.name ?? 'N/A' }}</h3>
                    <p>Route: {{ schedule.route?.name ?? 'N/A' }}</p>
                    <p>Train {{ schedule.train?.train_number ?? 'N/A' }} · {{ schedule.train?.train_name ?? 'Train' }}</p>
                    <p>Departure {{ schedule.departure_time ?? 'N/A' }}</p>
                  </article>
                  <article class="summary-card">
                    <span class="summary-label">Overview</span>
                    <p v-for="line in getSeatSummary()" :key="line">{{ line }}</p>
                  </article>
                </section>

                <section class="progression-panel" v-if="getStationProgression().length">
                  <h3>Route stations</h3>
                  <div class="station-strip">
                    <article v-for="stationItem in getStationProgression()" :key="stationItem.id" class="station-pill">
                      <span>{{ stationItem.station?.name ?? `Station ${stationItem.sequence}` }}</span>
                      <small v-if="getStationTiming(stationItem.station_id)?.arrival_time || getStationTiming(stationItem.station_id)?.departure_time" class="station-pill-times">
                        <span v-if="getStationTiming(stationItem.station_id)?.arrival_time">Arrives {{ getStationTiming(stationItem.station_id)?.arrival_time }}</span>
                        <span v-if="getStationTiming(stationItem.station_id)?.departure_time">Leaves {{ getStationTiming(stationItem.station_id)?.departure_time }}</span>
                      </small>
                    </article>
                  </div>
                </section>

                <section class="coach-carousel">
                  <div class="coach-carousel-header">
                    <h3>Train coaches</h3>
                    <div class="coach-controls">
                      <Button icon="pi pi-chevron-left" severity="secondary" text aria-label="Previous coach" @click="previousCoach" />
                      <span class="coach-counter">{{ activeCoachIndex + 1 }} / {{ getCoachRows().length }}</span>
                      <Button icon="pi pi-chevron-right" severity="secondary" text aria-label="Next coach" @click="nextCoach" />
                    </div>
                  </div>

                  <div ref="coachStrip" class="coach-strip" @scroll.passive="onCoachScroll">
                    <article v-for="coach in getCoachRows()" :key="coach.id" class="coach-item">
                      <div class="coach-item-header">
                        <div>
                          <p class="coach-title">Coach {{ coach.name ?? 'N/A' }}</p>
                          <p class="coach-meta">Type: {{ coach.type ?? 'N/A' }}</p>
                        </div>
                        <p class="coach-seat-count">{{ coach.seat_count ?? coach.total_seats ?? 0 }} seats</p>
                      </div>

                      <div class="coach-seat-map">
                        <div v-for="row in buildCoachSeatRows(coach)" :key="`${coach.id}-${row.left[0]?.seatNumber ?? 0}`" class="seat-row">
                          <div class="seat-group seat-group-left">
                            <span v-for="seat in row.left" :key="`${coach.id}-${seat.seatNumber}`" class="seat-box" :class="{ reserved: seat.isReserved }">
                              {{ seat.label }}
                            </span>
                          </div>
                          <div class="seat-gap" aria-hidden="true"></div>
                          <div class="seat-group seat-group-right">
                            <span v-for="seat in row.right" :key="`${coach.id}-${seat.seatNumber}`" class="seat-box" :class="{ reserved: seat.isReserved }">
                              {{ seat.label }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </article>
                  </div>
                </section>
              </template>
              <p v-else class="reservation-copy">No schedule details available.</p>
            </section>
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
  overflow-x: hidden;
}

.reservation-card {
  width: 100%;
  max-width: 100vw;
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
  overflow-x: hidden;
}

.reservation-stack {
  width: min(100%, 100%);
  max-width: 1180px;
}

.reservation-panel {
  margin-top: 1rem;
  padding: 1.5rem;
  border-radius: 18px;
  background: var(--accent-light);
  border: 1px solid var(--border-light);
  width: 100%;
  box-sizing: border-box;
}

.reservation-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.eyebrow {
  margin: 0 0 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.reservation-panel h2 {
  margin: 0;
}

.reservation-copy {
  color: var(--text-secondary);
}

.reservation-action {
  white-space: nowrap;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.summary-card,
.progression-panel,
.coach-item {
  padding: 1rem;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.45);
  border: 1px solid var(--border-light);
}

.summary-label {
  display: inline-block;
  margin-bottom: 0.5rem;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-secondary);
}

.progression-panel {
  margin-top: 1.5rem;
}

.progression-panel h3,
.coach-carousel h3 {
  margin: 0 0 1rem;
}

.station-strip {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.station-pill {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.station-pill-times {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  color: var(--text-secondary);
}

.station-pill {
  padding: 0.4rem 0.7rem;
  border-radius: 999px;
  background: var(--panel-bg);
  border: 1px solid var(--border-light);
}

.coach-carousel {
  margin-top: 1.5rem;
}

.coach-carousel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.coach-controls {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.coach-counter {
  min-width: 4.25rem;
  text-align: center;
  color: var(--text-secondary);
}

.coach-strip {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: minmax(100%, 1fr);
  gap: 1rem;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scroll-behavior: smooth;
  padding-bottom: 0.5rem;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

.coach-strip > * {
  scroll-snap-align: center;
}

.coach-item {
  min-height: 100%;
}

.coach-item-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.coach-title {
  font-weight: 700;
  margin: 0 0 0.2rem;
}

.coach-meta,
.coach-seat-count {
  margin: 0;
  color: var(--text-secondary);
}

.coach-seat-map {
  display: grid;
  gap: 0.75rem;
  max-width: 760px;
  margin: 0 auto;
}

.seat-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 1rem minmax(0, 1fr);
  align-items: center;
}

.seat-group {
  display: grid;
  grid-template-columns: repeat(2, minmax(7.2rem, 7.2rem));
  gap: 0.5rem;
  width: fit-content;
}

.seat-group-right {
  justify-self: end;
}

.seat-gap {
  height: 100%;
}

.seat-box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 5.8rem;
  border-radius: 14px;
  background: var(--panel-bg);
  border: 1px solid var(--border-light);
  font-weight: 600;
}

.seat-box.reserved {
  background: rgba(239, 68, 68, 0.14);
  color: #991b1b;
}

@media (max-width: 768px) {
  .reservation-header,
  .coach-carousel-header,
  .coach-item-header {
    flex-direction: column;
    align-items: stretch;
  }

  .coach-controls {
    justify-content: space-between;
  }

  .seat-row {
    grid-template-columns: 1fr 0.75rem 1fr;
  }

  .seat-group {
    grid-template-columns: repeat(2, minmax(6.5rem, 6.5rem));
  }
}
</style>
