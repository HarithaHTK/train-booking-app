<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import Message from 'primevue/message'
import { fetchCurrentUser, type AuthUser } from '../../api/auth'
import { fetchReservations, updateReservation, type Reservation } from '../../api/reservations'

const router = useRouter()
const loading = ref(true)
const message = ref('')
const error = ref('')
const reservations = ref<Reservation[]>([])
const cancelDialogVisible = ref(false)
const reservationToCancel = ref<Reservation | null>(null)
const savingCancellation = ref(false)
const user = ref<AuthUser | null>(null)

const activeReservations = computed(() => reservations.value)

function getRouteName(reservation: Reservation) {
  return reservation.schedule?.route?.name ?? `Schedule ${reservation.schedule_id}`
}

function getCoachSeatLabel(reservation: Reservation) {
  const seatNumber = reservation.seat?.seat_number ?? reservation.seat_id
  const coachName = reservation.seat?.coach?.name ?? reservation.seat?.coach_id ?? 'N/A'

  return `Coach ${coachName} Seat ${seatNumber}`
}

function formatDate(value?: string | null) {
  if (!value) return 'N/A'
  const parsed = new Date(value)
  return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleDateString()
}

function formatStatus(status?: string | null) {
  if (!status) return 'N/A'
  return status.charAt(0).toUpperCase() + status.slice(1)
}

async function loadUser() {
  const response = await fetchCurrentUser()
  const roleNames = response.roles ?? response.user.roles ?? []
  user.value = { ...response.user, roles: roleNames }
  localStorage.setItem('auth_user', JSON.stringify(user.value))
}

async function loadReservations() {
  const response = await fetchReservations()
  reservations.value = response.reservations ?? []
}

function askCancelReservation(reservation: Reservation) {
  reservationToCancel.value = reservation
  cancelDialogVisible.value = true
}

async function confirmCancelReservation() {
  if (!reservationToCancel.value) return

  savingCancellation.value = true
  try {
    await updateReservation(reservationToCancel.value.id, { status: 'cancelled' })
    message.value = 'Reservation cancelled successfully.'
    error.value = ''
    cancelDialogVisible.value = false
    reservationToCancel.value = null
    await loadReservations()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to cancel reservation'
  } finally {
    savingCancellation.value = false
  }
}

function canCancelReservation(reservation: Reservation) {
  return reservation.status === 'pending' || reservation.status === 'confirmed'
}

onMounted(async () => {
  try {
    await loadUser()
    await loadReservations()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load reservations'
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    router.push({ name: 'auth-login' })
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <main class="reservations-shell">
    <Card class="reservations-card shadow-sm">
      <template #content>
        <div class="reservations-content">
          <p v-if="loading" class="text-light-emphasis">Loading your reservations...</p>
          <div v-else>
            <Message v-if="message" severity="success" class="mb-3">{{ message }}</Message>
            <Message v-if="error" severity="error" class="mb-3">{{ error }}</Message>

            <div class="reservations-header">
              <div>
                <p class="eyebrow">Member area</p>
                <h2>My Reservations</h2>
              </div>
              <Button label="Back to dashboard" severity="secondary" @click="router.push({ name: 'dashboard' })" />
            </div>

            <DataTable
              :value="activeReservations"
              responsiveLayout="scroll"
              stripedRows
              showGridlines
              class="reservations-table"
              :emptyMessage="'No reservations found.'"
            >
              <Column field="id" header="ID" />
              <Column header="Route">
                <template #body="slotProps">
                  {{ getRouteName(slotProps.data) }}
                </template>
              </Column>
              <Column header="Train">
                <template #body="slotProps">
                  {{ slotProps.data.schedule?.train?.train_name ?? 'Train' }}
                  <span v-if="slotProps.data.schedule?.train?.train_number">({{ slotProps.data.schedule.train.train_number }})</span>
                </template>
              </Column>
              <Column header="Travel date">
                <template #body="slotProps">
                  {{ formatDate(slotProps.data.travel_date) }}
                </template>
              </Column>
              <Column header="Seat">
                <template #body="slotProps">
                  {{ getCoachSeatLabel(slotProps.data) }}
                </template>
              </Column>
              <Column header="Status">
                <template #body="slotProps">
                  {{ formatStatus(slotProps.data.status) }}
                </template>
              </Column>
              <Column header="Action">
                <template #body="slotProps">
                  <Button
                    v-if="canCancelReservation(slotProps.data)"
                    label="Cancel"
                    severity="danger"
                    text
                    @click="askCancelReservation(slotProps.data)"
                  />
                  <span v-else class="reservation-muted">Not cancellable</span>
                </template>
              </Column>
            </DataTable>
          </div>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="cancelDialogVisible" header="Cancel reservation" modal :style="{ width: '28rem' }">
      <p>
        Cancel reservation #{{ reservationToCancel?.id }}?
      </p>
      <template #footer>
        <Button label="No, keep it" severity="secondary" text @click="cancelDialogVisible = false" />
        <Button label="Yes, cancel" severity="danger" :loading="savingCancellation" @click="confirmCancelReservation" />
      </template>
    </Dialog>
  </main>
</template>

<style scoped>
.reservations-shell {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 0;
  background: linear-gradient(135deg, var(--bg-secondary), var(--bg-tertiary));
}

.reservations-card {
  width: 100%;
  min-height: 100vh;
  border-radius: 0;
  box-shadow: none;
}

.reservations-content {
  padding: 2rem;
}

.reservations-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.eyebrow {
  margin: 0 0 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-secondary);
  font-size: 0.8rem;
}

.reservation-muted {
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.reservations-table {
  width: 100%;
}
</style>
