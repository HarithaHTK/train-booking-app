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

type JourneyRouteStation = {
  id: number
  route_id: number
  station_id: number
  sequence: number
  station?: Station
}

type JourneyRoute = {
  id: number
  name: string
  description?: string
  is_active: boolean
  matched_station_id?: number
  matched_sequence?: number | null
  forward_stations?: JourneyRouteStation[]
  stations?: JourneyRouteStation[]
}

type JourneySchedule = {
  id: number
  route_id: number
  train_id: number
  departure_time?: string | null
  is_active: boolean
  route?: {
    id: number
    name: string
  }
  train?: {
    id: number
    train_number: string
    train_name: string
    coaches?: Array<{
      id: number
      name?: string
      type?: string | null
      seat_count?: number | null
      total_seats?: number | null
    }>
  }
  station_schedules?: Array<{
    id: number
    station_id: number
    sequence: number
    arrival_time?: string | null
    departure_time?: string | null
    station?: Station
  }>
}

type JourneyStationTiming = {
  stationName: string
  arrivalTime?: string | null
  departureTime?: string | null
}

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
const journeyLoading = ref(false)
const journeyRoutes = ref<JourneyRoute[]>([])
const journeySchedules = ref<JourneySchedule[]>([])

function toDateTime(value: Date | string | null | undefined) {
  if (!value) return null
  if (value instanceof Date) return value

  const parsed = new Date(value)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

function getSelectedDateTime() {
  const date = toDateTime(journeyDate.value)
  const time = toDateTime(journeyTime.value)
  if (!date || !time) return null

  const result = new Date(date)
  result.setHours(time.getHours(), time.getMinutes(), time.getSeconds(), 0)
  return result
}

function parseTimeToDate(base: Date, timeValue?: string | null) {
  if (!timeValue) return null

  const parts = timeValue.split(':').map((part) => Number(part))
  if (parts.some((part) => Number.isNaN(part))) return null

  const result = new Date(base)
  result.setHours(parts[0] ?? 0, parts[1] ?? 0, parts[2] ?? 0, 0)
  return result
}

function getScheduleStation(schedule: JourneySchedule, stationId: number | null) {
  if (!stationId) return null
  return schedule.station_schedules?.find((stationSchedule) => stationSchedule.station_id === stationId) ?? null
}

function isMatchingSchedule(schedule: JourneySchedule) {
  const selectedDateTime = getSelectedDateTime()
  if (!selectedDateTime) return false

  const startingStation = getScheduleStation(schedule, journeyFrom.value)
  const departureTime = startingStation?.departure_time ?? schedule.departure_time
  const scheduleDateTime = parseTimeToDate(selectedDateTime, departureTime)

  return Boolean(scheduleDateTime && scheduleDateTime.getTime() >= selectedDateTime.getTime())
}

function getJourneyStationTimings(schedule: JourneySchedule): JourneyStationTiming[] {
  return [journeyFrom.value, journeyTo.value]
    .filter((stationId): stationId is number => Boolean(stationId))
    .map((stationId) => {
      const stationSchedule = getScheduleStation(schedule, stationId)
      const stationName = stationSchedule?.station?.name ?? `Station ${stationId}`

      return {
        stationName,
        arrivalTime: stationSchedule?.arrival_time ?? null,
        departureTime: stationSchedule?.departure_time ?? null,
      }
    })
}

function getCoachRows(schedule: JourneySchedule) {
  return schedule.train?.coaches ?? []
}

function getTotalCoaches(schedule: JourneySchedule) {
  return getCoachRows(schedule).length
}

function getReservableCoaches(schedule: JourneySchedule) {
  return getCoachRows(schedule).filter((coach) => coach.type === 'reserved').length
}

function getTotalSeats(schedule: JourneySchedule) {
  return getCoachRows(schedule).reduce((sum, coach) => sum + (coach.seat_count ?? coach.total_seats ?? 0), 0)
}

function openReservation(schedule: JourneySchedule) {
  const selectedDate = journeyDate.value
  const selectedTime = journeyTime.value

  router.push({
    name: 'reservation-detail',
    params: { scheduleId: String(schedule.id) },
    query: {
      from: journeyFrom.value ? String(journeyFrom.value) : undefined,
      to: journeyTo.value ? String(journeyTo.value) : undefined,
      date: selectedDate ? selectedDate.toISOString().slice(0, 10) : undefined,
      time: selectedTime
        ? `${String(selectedTime.getHours()).padStart(2, '0')}:${String(selectedTime.getMinutes()).padStart(2, '0')}`
        : undefined,
    },
  })
}

const fromOptions = computed(() => stations.value.filter((station) => station.id !== journeyTo.value))
const toOptions = computed(() => stations.value.filter((station) => station.id !== journeyFrom.value))

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

  journeyLoading.value = true
  journeyRoutes.value = []
  journeySchedules.value = []
  stationError.value = ''
  message.value = ''
  error.value = ''

  fetch(`/api/route-search/by-station/${journeyFrom.value}`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${localStorage.getItem('auth_token') ?? ''}`,
    },
  })
    .then(async (response) => {
      const data = await response.json().catch(() => ({}))

      if (!response.ok) {
        throw new Error(data.message || 'Unable to load matching routes')
      }

      journeyRoutes.value = data.routes ?? []
      journeySchedules.value = (data.schedules ?? []).filter((schedule: JourneySchedule) => isMatchingSchedule(schedule))
    })
    .catch((err) => {
      stationError.value = err instanceof Error ? err.message : 'Unable to load matching routes'
    })
    .finally(() => {
      journeyLoading.value = false
    })
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

              <p v-if="journeyLoading" class="journey-status">Loading matching routes and schedules...</p>

              <div v-if="journeyRoutes.length || journeySchedules.length" class="journey-results">
                <section v-if="journeyRoutes.length" class="journey-results-block">
                  <h3>Matching routes</h3>
                  <ul>
                    <li v-for="route in journeyRoutes" :key="route.id">
                      <strong>{{ route.name }}</strong>
                      <span v-if="route.description"> — {{ route.description }}</span>
                    </li>
                  </ul>
                </section>

                <Message v-if="!journeySchedules.length" severity="info" class="journey-empty-state">
                  No matching schedules were found for the selected station, date, and time.
                </Message>

                <section v-if="journeySchedules.length" class="journey-results-block">
                  <h3>Matching schedules</h3>
                  <ul>
                    <li v-for="schedule in journeySchedules" :key="schedule.id">
                      <strong>{{ schedule.train?.train_name ?? 'Train' }}</strong>
                      <span v-if="schedule.train?.train_number"> ({{ schedule.train.train_number }})</span>
                      <span v-if="schedule.departure_time"> — departs {{ schedule.departure_time }}</span>
                      <div v-for="stationTiming in getJourneyStationTimings(schedule)" :key="`${schedule.id}-${stationTiming.stationName}`" class="journey-station-summary">
                        <span>{{ stationTiming.stationName }}</span>
                        <span v-if="stationTiming.arrivalTime">Arrives {{ stationTiming.arrivalTime }}</span>
                        <span v-if="stationTiming.departureTime">Leaves {{ stationTiming.departureTime }}</span>
                      </div>
                      <div class="journey-train-summary">
                        <span>Total coaches: {{ getTotalCoaches(schedule) }}</span>
                        <span>Reservable coaches: {{ getReservableCoaches(schedule) }}</span>
                        <span>Total seats: {{ getTotalSeats(schedule) }}</span>
                      </div>
                      <Button label="Make reservation" class="mt-2" @click="openReservation(schedule)" />
                    </li>
                  </ul>
                </section>
              </div>
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

.journey-status {
  margin: 1rem 0 0;
  color: var(--text-secondary);
}

.journey-results {
  margin-top: 1.25rem;
  display: grid;
  gap: 1rem;
}

.journey-empty-state {
  margin-top: 0.25rem;
}

.journey-results-block {
  padding: 1rem;
  border-radius: 14px;
  background: var(--panel-bg);
  border: 1px solid var(--border-light);
}

.journey-results-block h3 {
  margin: 0 0 0.75rem;
  font-size: 1rem;
}

.journey-results-block ul {
  margin: 0;
  padding-left: 1.2rem;
}

.journey-train-summary {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem 1.25rem;
  margin-top: 0.35rem;
  color: var(--text-secondary);
  font-size: 0.92rem;
}

.journey-station-summary {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1rem;
  margin-top: 0.35rem;
  color: var(--text-secondary);
  font-size: 0.92rem;
}

.error {
  color: var(--accent-primary);
}
</style>
