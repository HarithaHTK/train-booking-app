<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import Card from 'primevue/card'
import Message from 'primevue/message'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import { fetchCurrentUser, type AuthUser } from '../../api/auth'
import { createReservation, fetchSchedule, type Schedule, type ScheduleCoach } from '../../api/schedules'
import { fetchStations, type Station } from '../../api/stations'

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
const stations = ref<Station[]>([])
const stationsLoading = ref(false)
const journeyStartStation = ref<number | null>(null)
const journeyEndStation = ref<number | null>(null)
const journeyDate = ref<Date | null>(null)
const journeyTime = ref<Date | null>(null)
const journeyReady = ref(false)
const selectedSeatKeys = ref<Set<string>>(new Set())
const bookingDialogVisible = ref(false)

const scheduleId = computed(() => Number(route.params.scheduleId))
const journeyQueryFrom = computed(() => Number(route.query.from))
const journeyQueryTo = computed(() => Number(route.query.to))

function parseDateQuery(value: unknown) {
  if (typeof value !== 'string' || !value) return null
  const parsed = new Date(value)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

function parseTimeQuery(value: unknown) {
  if (typeof value !== 'string' || !value) return null

  const [hours, minutes, seconds = '0'] = value.split(':')
  const hour = Number(hours)
  const minute = Number(minutes)
  const second = Number(seconds)

  if ([hour, minute, second].some((part) => Number.isNaN(part))) return null

  const time = new Date()
  time.setHours(hour, minute, second, 0)
  return time
}

function formatDateQuery(value: Date | null) {
  return value ? value.toISOString().slice(0, 10) : undefined
}

function formatTimeQuery(value: Date | null) {
  if (!value) return undefined
  return `${String(value.getHours()).padStart(2, '0')}:${String(value.getMinutes()).padStart(2, '0')}`
}

function routeStationOptions() {
  return (schedule.value?.route?.stations ?? [])
    .slice()
    .sort((a, b) => a.sequence - b.sequence)
    .map((stationItem) => stationItem.station)
    .filter((station): station is Station => Boolean(station))
}

const startStationOptions = computed(() => routeStationOptions().filter((station) => station.id !== journeyEndStation.value))
const endStationOptions = computed(() => routeStationOptions().filter((station) => station.id !== journeyStartStation.value))

type CoachSeat = {
  id: number
  seatNumber: number
  label: string
  isReserved: boolean
}

type CoachSeatRow = {
  left: CoachSeat[]
  right: CoachSeat[]
}

type SelectedSeatDetail = {
  coachId: number
  coachName: string
  coachType: string
  seatId: number
  seatLabel: string
  seatNumber: number
  seatStatus: string
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
        id: Number(seat.id ?? index + 1),
        seatNumber: Number.isFinite(Number(seat.seat_number)) ? Number(seat.seat_number) : index + 1,
        label: `S${Number.isFinite(Number(seat.seat_number)) ? Number(seat.seat_number) : index + 1}`,
        isReserved: Boolean(seat.is_reserved),
      }))
      .slice(0, seatCount || seats.length)
  }

  return Array.from({ length: seatCount }, (_, index) => ({
    id: index + 1,
    seatNumber: index + 1,
    label: `S${index + 1}`,
    isReserved: false,
  }))
}

function isReservedCoach(coach: ScheduleCoach) {
  return String(coach.type ?? '').toLowerCase() === 'reserved'
}

function getSeatKey(coach: ScheduleCoach, seat: CoachSeat) {
  return `${coach.id}:${seat.id}:${seat.seatNumber}`
}

function isSeatSelectable(coach: ScheduleCoach, seat: CoachSeat) {
  return isReservedCoach(coach) && !seat.isReserved
}

function isSeatSelected(coach: ScheduleCoach, seat: CoachSeat) {
  return selectedSeatKeys.value.has(getSeatKey(coach, seat))
}

function toggleSeatSelection(coach: ScheduleCoach, seat: CoachSeat) {
  if (!isSeatSelectable(coach, seat)) return

  const key = getSeatKey(coach, seat)
  const next = new Set(selectedSeatKeys.value)

  if (next.has(key)) {
    next.delete(key)
  } else {
    next.add(key)
  }

  selectedSeatKeys.value = next
}

function resetSelection() {
  selectedSeatKeys.value = new Set()
}

function getSelectedSeats() {
  return (schedule.value?.train?.coaches ?? []).flatMap((coach) =>
    getSeatsForCoach(coach)
      .filter((seat) => isSeatSelected(coach, seat))
      .map((seat) => ({
        coachId: coach.id,
        coachName: coach.name ?? 'N/A',
        coachType: coach.type ?? 'N/A',
        seatId: seat.id,
        seatLabel: seat.label,
        seatNumber: seat.seatNumber,
        seatStatus: seat.isReserved ? 'Reserved' : 'Available',
      })),
  ) as SelectedSeatDetail[]
}

function getJourneyStationName(stationId: number | null) {
  if (!stationId) return 'N/A'
  return stations.value.find((station) => station.id === stationId)?.name ?? `Station ${stationId}`
}

function getSelectedReservationRows() {
  return getSelectedSeats().map((seat) => ({
    ...seat,
    scheduleId: schedule.value?.id ?? scheduleId.value,
    routeName: schedule.value?.route?.name ?? 'N/A',
    trainNumber: schedule.value?.train?.train_number ?? 'N/A',
    trainName: schedule.value?.train?.train_name ?? 'Train',
    departureTime: schedule.value?.departure_time ?? 'N/A',
    journeyStart: getJourneyStationName(journeyStartStation.value),
    journeyEnd: getJourneyStationName(journeyEndStation.value),
    journeyDate: journeyDate.value ? journeyDate.value.toISOString().slice(0, 10) : 'N/A',
    journeyTime: journeyTime.value ? `${String(journeyTime.value.getHours()).padStart(2, '0')}:${String(journeyTime.value.getMinutes()).padStart(2, '0')}` : 'N/A',
    status: 'pending',
  }))
}

function openBookingDialog() {
  if (!getSelectedSeats().length) {
    error.value = 'Please select one or more seats to book.'
    return
  }

  error.value = ''
  bookingDialogVisible.value = true
}

async function confirmBooking() {
  const selectedSeats = getSelectedSeats()
  if (!selectedSeats.length) {
    bookingDialogVisible.value = false
    error.value = 'Please select one or more seats to book.'
    return
  }

  try {
    const response = await createReservation({
      schedule_id: scheduleId.value,
      start_station_id: journeyStartStation.value ?? 0,
      leave_station_id: journeyEndStation.value ?? 0,
      seat_ids: selectedSeats.map((seat) => seat.seatId),
      status: 'confirmed',
    })

    message.value = response.message || 'Reservation created successfully.'
    error.value = ''
    bookingDialogVisible.value = false
    resetSelection()
    await loadSchedule()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to create reservation.'
  }
}

function bookSelectedSeats() {
  openBookingDialog()
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

function syncJourneyFromQuery() {
  const from = journeyQueryFrom.value
  const to = journeyQueryTo.value
  const date = parseDateQuery(route.query.date)
  const time = parseTimeQuery(route.query.time)

  if (!Number.isFinite(from) || !Number.isFinite(to) || !date || !time) {
    router.replace({ name: 'dashboard' })
    return false
  }

  journeyStartStation.value = from
  journeyEndStation.value = to
  journeyDate.value = date
  journeyTime.value = time
  journeyReady.value = true
  return true
}

function syncQueryFromJourney() {
  router.replace({
    name: 'reservation-detail',
    params: { scheduleId: String(scheduleId.value) },
    query: {
      from: journeyStartStation.value ? String(journeyStartStation.value) : undefined,
      to: journeyEndStation.value ? String(journeyEndStation.value) : undefined,
      date: formatDateQuery(journeyDate.value),
      time: formatTimeQuery(journeyTime.value),
    },
  })
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

async function loadStations() {
  stationsLoading.value = true
  try {
    const response = await fetchStations()
    stations.value = response.stations
  } catch {
    stations.value = []
  } finally {
    stationsLoading.value = false
  }
}

watch([journeyStartStation, journeyEndStation, journeyDate, journeyTime], () => {
  if (!journeyReady.value) return
  syncQueryFromJourney()
})

onMounted(() => {
  if (!syncJourneyFromQuery()) return

  loadUser()
  loadSchedule()
  loadStations()
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
                    <span class="summary-label">Journey start</span>
                    <Dropdown
                      v-model="journeyStartStation"
                      :options="startStationOptions"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Select start station"
                      class="w-100"
                      :loading="stationsLoading"
                    />
                  </article>
                  <article class="summary-card">
                    <span class="summary-label">Journey end</span>
                    <Dropdown
                      v-model="journeyEndStation"
                      :options="endStationOptions"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Select end station"
                      class="w-100"
                      :loading="stationsLoading"
                    />
                  </article>
                  <article class="summary-card">
                    <span class="summary-label">Journey date</span>
                    <Calendar v-model="journeyDate" dateFormat="yy-mm-dd" class="w-100" showIcon />
                  </article>
                  <article class="summary-card">
                    <span class="summary-label">Journey time</span>
                    <Calendar v-model="journeyTime" timeOnly hourFormat="24" class="w-100" showIcon />
                  </article>
                </section>

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

                  <div class="selection-actions">
                    <div class="selection-summary">
                      <span class="summary-label">Selected seats</span>
                      <p>{{ getSelectedSeats().length }} seat{{ getSelectedSeats().length === 1 ? '' : 's' }} selected</p>
                    </div>
                    <div class="selection-buttons">
                      <Button label="Reset selection" severity="secondary" outlined @click="resetSelection" />
                      <Button label="Book" icon="pi pi-check" :disabled="!getSelectedSeats().length" @click="bookSelectedSeats" />
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

                      <Message v-if="!isReservedCoach(coach)" severity="warn" class="coach-warning mb-3">
                        Unreserved coach seats cannot be selected.
                      </Message>

                      <div class="coach-seat-map">
                        <div v-for="row in buildCoachSeatRows(coach)" :key="`${coach.id}-${row.left[0]?.seatNumber ?? 0}`" class="seat-row">
                          <div class="seat-group seat-group-left">
                            <button
                              v-for="seat in row.left"
                              :key="`${coach.id}-${seat.seatNumber}`"
                              type="button"
                              class="seat-box"
                              :class="{ reserved: seat.isReserved, selectable: isSeatSelectable(coach, seat), selected: isSeatSelected(coach, seat), locked: !isSeatSelectable(coach, seat) }"
                              :disabled="!isSeatSelectable(coach, seat)"
                              @click="toggleSeatSelection(coach, seat)"
                            >
                              {{ seat.label }}
                            </button>
                          </div>
                          <div class="seat-gap" aria-hidden="true"></div>
                          <div class="seat-group seat-group-right">
                            <button
                              v-for="seat in row.right"
                              :key="`${coach.id}-${seat.seatNumber}`"
                              type="button"
                              class="seat-box"
                              :class="{ reserved: seat.isReserved, selectable: isSeatSelectable(coach, seat), selected: isSeatSelected(coach, seat), locked: !isSeatSelectable(coach, seat) }"
                              :disabled="!isSeatSelectable(coach, seat)"
                              @click="toggleSeatSelection(coach, seat)"
                            >
                              {{ seat.label }}
                            </button>
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

    <Dialog v-model:visible="bookingDialogVisible" modal header="Confirm reservation" :style="{ width: 'min(96vw, 920px)' }" :draggable="false">
      <div class="booking-dialog">
        <section class="booking-dialog-section">
          <h3>Reservation payload</h3>
          <div class="booking-details-grid">
            <article class="booking-detail-card">
              <span class="summary-label">Schedule</span>
              <p>{{ schedule?.id ?? scheduleId }}</p>
            </article>
            <article class="booking-detail-card">
              <span class="summary-label">Start station</span>
              <p>{{ getJourneyStationName(journeyStartStation) }}</p>
            </article>
            <article class="booking-detail-card">
              <span class="summary-label">Leave station</span>
              <p>{{ getJourneyStationName(journeyEndStation) }}</p>
            </article>
            <article class="booking-detail-card">
              <span class="summary-label">Status</span>
              <p>pending</p>
            </article>
          </div>
        </section>

        <section class="booking-dialog-section">
          <h3>Trip details</h3>
          <div class="booking-details-grid">
            <article class="booking-detail-card">
              <span class="summary-label">Train</span>
              <p>{{ schedule?.train?.train_number ?? 'N/A' }} · {{ schedule?.train?.train_name ?? 'Train' }}</p>
            </article>
            <article class="booking-detail-card">
              <span class="summary-label">Route</span>
              <p>{{ schedule?.route?.name ?? 'N/A' }}</p>
            </article>
            <article class="booking-detail-card">
              <span class="summary-label">Departure</span>
              <p>{{ schedule?.departure_time ?? 'N/A' }}</p>
            </article>
            <article class="booking-detail-card">
              <span class="summary-label">Journey</span>
              <p>{{ getJourneyStationName(journeyStartStation) }} → {{ getJourneyStationName(journeyEndStation) }}</p>
              <p>{{ journeyDate ? journeyDate.toISOString().slice(0, 10) : 'N/A' }} · {{ journeyTime ? `${String(journeyTime.getHours()).padStart(2, '0')}:${String(journeyTime.getMinutes()).padStart(2, '0')}` : 'N/A' }}</p>
            </article>
          </div>
        </section>

        <section class="booking-dialog-section">
          <h3>Selected seats</h3>
          <div class="booking-seat-list">
            <article v-for="seat in getSelectedReservationRows()" :key="`${seat.coachId}-${seat.seatId}`" class="booking-seat-item">
              <div>
                <p class="booking-seat-title">Seat {{ seat.seatLabel }}</p>
                <p>Coach {{ seat.coachName }} · {{ seat.coachType }}</p>
              </div>
              <div class="booking-seat-meta">
                <span>Seat #{{ seat.seatNumber }}</span>
                <span>{{ seat.seatStatus }}</span>
              </div>
            </article>
          </div>
        </section>

        <div class="booking-dialog-actions">
          <Button label="Cancel" severity="secondary" text @click="bookingDialogVisible = false" />
          <Button label="Confirm booking" icon="pi pi-check" @click="confirmBooking" />
        </div>
      </div>
    </Dialog>
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

.coach-warning {
  margin-bottom: 0.75rem;
}

.selection-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
  padding: 0.9rem 1rem;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.45);
  border: 1px solid var(--border-light);
}

.selection-summary p {
  margin: 0.15rem 0 0;
  font-weight: 600;
}

.selection-buttons {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
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
  width: 100%;
  cursor: pointer;
}

.seat-box.reserved {
  background: rgba(239, 68, 68, 0.14);
  color: #991b1b;
}

.seat-box.selectable:hover {
  border-color: var(--primary-color);
}

.seat-box.selected {
  background: rgba(37, 99, 235, 0.15);
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.seat-box.locked {
  cursor: not-allowed;
  opacity: 0.55;
}

.seat-box:disabled {
  pointer-events: none;
}

.booking-dialog {
  display: grid;
  gap: 1.25rem;
}

.booking-dialog-section h3 {
  margin: 0 0 0.75rem;
}

.booking-details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.75rem;
}

.booking-detail-card,
.booking-seat-item {
  padding: 0.9rem 1rem;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.45);
  border: 1px solid var(--border-light);
}

.booking-detail-card p,
.booking-seat-item p {
  margin: 0.15rem 0 0;
}

.booking-seat-list {
  display: grid;
  gap: 0.75rem;
}

.booking-seat-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.booking-seat-title {
  font-weight: 700;
}

.booking-seat-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
  color: var(--text-secondary);
}

.booking-dialog-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

@media (max-width: 768px) {
  .reservation-header,
  .coach-carousel-header,
  .coach-item-header,
  .selection-actions,
  .booking-seat-item,
  .booking-dialog-actions {
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

  .booking-seat-meta {
    align-items: flex-start;
  }
}
</style>
