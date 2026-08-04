<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import { fetchCurrentUser } from '../../api/auth'
import { fetchStations, type Station } from '../../api/stations'
import { fetchCoaches, type Coach } from '../../api/coaches'
import {
  deleteReservation,
  fetchReservation,
  fetchReservations,
  updateReservation,
  type Reservation,
  type ReservationStatus,
} from '../../api/reservations'

const router = useRouter()
const loading = ref(true)
const error = ref('')
const message = ref('')
const reservationsLoading = ref(false)
const savingReservation = ref(false)
const rowActionLoadingId = ref<number | null>(null)
const reservations = ref<Reservation[]>([])
const stationLookup = ref<Map<number, Station>>(new Map())
const coachLookup = ref<Map<number, Coach>>(new Map())
const showDeleted = ref(false)
const editDialogVisible = ref(false)
const editingReservationId = ref<number | null>(null)
const reservationForm = ref({
  schedule_id: '',
  start_station_id: '',
  leave_station_id: '',
  seat_id: '',
  status: 'pending' as ReservationStatus,
  checked_in_at: '',
  checked_out_at: '',
})
const validationError = ref('')

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Confirmed', value: 'confirmed' },
  { label: 'Cancelled', value: 'cancelled' },
  { label: 'Completed', value: 'completed' },
]

const visibleReservations = computed(() => reservations.value)

function formatText(value?: string | number | null) {
  return value === null || value === undefined || value === '' ? '-' : String(value)
}

function formatUser(reservation: Reservation) {
  return reservation.user?.name || reservation.user?.email || `User #${reservation.user_id}`
}

function formatTrain(reservation: Reservation) {
  const train = reservation.schedule?.train
  if (!train) return `Schedule #${reservation.schedule_id}`
  return `${train.train_number ?? 'Train'}${train.train_name ? ` - ${train.train_name}` : ''}`
}

function formatStations(reservation: Reservation) {
  const start = reservation.startStation?.name || stationLookup.value.get(reservation.start_station_id)?.name || `Station #${reservation.start_station_id}`
  const leave = reservation.leaveStation?.name || stationLookup.value.get(reservation.leave_station_id)?.name || `Station #${reservation.leave_station_id}`
  return `${start} → ${leave}`
}

function formatSeat(reservation: Reservation) {
  const coachName = reservation.seat?.coach?.name ?? (reservation.seat?.coach_id ? coachLookup.value.get(reservation.seat.coach_id)?.name : undefined)
  const seatNumber = reservation.seat?.seat_number ?? `Seat #${reservation.seat_id}`
  if (!coachName) return seatNumber
  return `${seatNumber} · Coach ${coachName}`
}

async function loadUser() {
  try {
    await fetchCurrentUser()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load account'
    localStorage.removeItem('admin_auth_token')
    localStorage.removeItem('admin_auth_user')
    router.push({ name: 'auth-login' })
  } finally {
    loading.value = false
  }
}

async function loadReservations() {
  reservationsLoading.value = true
  try {
    const res = await fetchReservations({ trashed: showDeleted.value })
    reservations.value = res.reservations
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load reservations'
  } finally {
    reservationsLoading.value = false
  }
}

async function loadLookups() {
  try {
    const stationsRes = await fetchStations()
    const coachesRes = await fetchCoaches()
    stationLookup.value = new Map(stationsRes.stations.map((station) => [station.id, station]))
    coachLookup.value = new Map(coachesRes.coaches.map((coach) => [coach.id, coach]))
  } catch {
    stationLookup.value = new Map()
    coachLookup.value = new Map()
  }
}

function resetForm() {
  editingReservationId.value = null
  validationError.value = ''
  reservationForm.value = {
    schedule_id: '',
    start_station_id: '',
    leave_station_id: '',
    seat_id: '',
    status: 'pending',
    checked_in_at: '',
    checked_out_at: '',
  }
}

async function openEditDialog(reservation: Reservation) {
  try {
    const res = await fetchReservation(reservation.id)
    const fullReservation = res.reservation
    editingReservationId.value = fullReservation.id
    reservationForm.value = {
      schedule_id: String(fullReservation.schedule_id),
      start_station_id: String(fullReservation.start_station_id),
      leave_station_id: String(fullReservation.leave_station_id),
      seat_id: String(fullReservation.seat_id),
      status: fullReservation.status,
      checked_in_at: fullReservation.checked_in_at ?? '',
      checked_out_at: fullReservation.checked_out_at ?? '',
    }
    editDialogVisible.value = true
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load reservation details'
  }
}

function closeDialog() {
  editDialogVisible.value = false
  resetForm()
}

async function saveReservation() {
  if (!editingReservationId.value) return

  const scheduleId = Number(reservationForm.value.schedule_id)
  const startStationId = Number(reservationForm.value.start_station_id)
  const leaveStationId = Number(reservationForm.value.leave_station_id)
  const seatId = Number(reservationForm.value.seat_id)

  if (!scheduleId || !startStationId || !leaveStationId || !seatId) {
    validationError.value = 'All reservation fields are required.'
    return
  }

  savingReservation.value = true
  try {
    await updateReservation(editingReservationId.value, {
      schedule_id: scheduleId,
      start_station_id: startStationId,
      leave_station_id: leaveStationId,
      seat_id: seatId,
      status: reservationForm.value.status,
      checked_in_at: reservationForm.value.checked_in_at || null,
      checked_out_at: reservationForm.value.checked_out_at || null,
    })
    message.value = 'Reservation updated successfully.'
    closeDialog()
    await loadReservations()
  } catch (err) {
    validationError.value = err instanceof Error ? err.message : 'Unable to save reservation'
  } finally {
    savingReservation.value = false
  }
}

async function removeReservation(reservation: Reservation) {
  const confirmed = window.confirm(`Delete reservation #${reservation.id}? This cannot be undone.`)
  if (!confirmed) return

  rowActionLoadingId.value = reservation.id
  try {
    await deleteReservation(reservation.id)
    message.value = 'Reservation deleted successfully.'
    await loadReservations()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to delete reservation'
  } finally {
    rowActionLoadingId.value = null
  }
}

async function toggleDeletedReservations() {
  showDeleted.value = !showDeleted.value
  await loadReservations()
}

onMounted(() => {
  Promise.all([loadUser(), loadReservations(), loadLookups()])
})
</script>

<template>
  <main class="dashboard-shell">
    <Card class="dashboard-card shadow-sm">
      <template #content>
        <div class="dashboard-content">
          <p v-if="loading" class="text-light-emphasis">Loading your account...</p>

          <div v-else class="route-page">
            <div class="route-header">
              <div>
                <h1 class="route-title">Reservations Management</h1>
                <p class="route-subtitle">Review, edit, delete, and change reservation status.</p>
              </div>

              <Button
                :label="showDeleted ? 'Show Active' : 'Show Deleted'"
                icon="pi pi-filter"
                class="route-add-btn"
                outlined
                @click="toggleDeletedReservations"
              />
            </div>

            <Message severity="success" class="mb-3" v-if="message">{{ message }}</Message>
            <Message v-if="error" severity="error" class="mb-3">{{ error }}</Message>

            <Card class="route-card">
              <template #content>
                <div v-if="reservationsLoading" class="route-empty-state">
                  <i class="pi pi-spin pi-spinner route-empty-icon" />
                  <p>Loading reservations...</p>
                </div>

                <DataTable v-else :value="visibleReservations" dataKey="id" stripedRows responsiveLayout="scroll" :paginator="visibleReservations.length > 10" :rows="10">
                  <Column field="id" header="#" />
                  <Column header="User">
                    <template #body="slotProps">
                      {{ formatUser(slotProps.data) }}
                    </template>
                  </Column>
                  <Column header="Train">
                    <template #body="slotProps">
                      {{ formatTrain(slotProps.data) }}
                    </template>
                  </Column>
                  <Column header="Stations">
                    <template #body="slotProps">
                      {{ formatStations(slotProps.data) }}
                    </template>
                  </Column>
                  <Column header="Seat">
                    <template #body="slotProps">
                      {{ formatSeat(slotProps.data) }}
                    </template>
                  </Column>
                  <Column field="status" header="Status" />
                  <Column header="Checked In">
                    <template #body="slotProps">
                      {{ formatText(slotProps.data.checked_in_at) }}
                    </template>
                  </Column>
                  <Column header="Checked Out">
                    <template #body="slotProps">
                      {{ formatText(slotProps.data.checked_out_at) }}
                    </template>
                  </Column>
                  <Column header="Deleted">
                    <template #body="slotProps">
                      {{ formatText(slotProps.data.deleted_at) }}
                    </template>
                  </Column>
                  <Column header="Actions">
                    <template #body="slotProps">
                      <div class="table-actions">
                        <Button icon="pi pi-pencil" aria-label="Edit reservation" size="small" severity="secondary" text rounded @click="openEditDialog(slotProps.data)" />
                        <Button icon="pi pi-trash" aria-label="Delete reservation" size="small" severity="danger" text rounded :loading="rowActionLoadingId === slotProps.data.id" @click="removeReservation(slotProps.data)" />
                      </div>
                    </template>
                  </Column>
                </DataTable>
              </template>
            </Card>
          </div>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="editDialogVisible" modal header="Edit Reservation" :style="{ width: '42rem' }" @hide="closeDialog">
      <div class="dialog-form">
        <Message v-if="validationError" severity="error">{{ validationError }}</Message>

        <div class="form-grid">
          <label>
            Schedule ID
            <InputText v-model="reservationForm.schedule_id" type="number" />
          </label>
          <label>
            Start Station ID
            <InputText v-model="reservationForm.start_station_id" type="number" />
          </label>
          <label>
            Leave Station ID
            <InputText v-model="reservationForm.leave_station_id" type="number" />
          </label>
          <label>
            Seat ID
            <InputText v-model="reservationForm.seat_id" type="number" />
          </label>
          <label>
            Status
            <Dropdown v-model="reservationForm.status" :options="statusOptions" optionLabel="label" optionValue="value" />
          </label>
          <label>
            Checked In At
            <InputText v-model="reservationForm.checked_in_at" placeholder="YYYY-MM-DD HH:MM:SS" />
          </label>
          <label>
            Checked Out At
            <InputText v-model="reservationForm.checked_out_at" placeholder="YYYY-MM-DD HH:MM:SS" />
          </label>
        </div>

        <div class="dialog-actions">
          <Button label="Cancel" severity="secondary" outlined @click="closeDialog" />
          <Button label="Save" :loading="savingReservation" @click="saveReservation" />
        </div>
      </div>
    </Dialog>
  </main>
</template>