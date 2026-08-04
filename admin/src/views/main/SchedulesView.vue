<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import InputSwitch from 'primevue/inputswitch'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import { fetchCurrentUser } from '../../api/auth'
import { fetchRoutes, type Route } from '../../api/routes'
import { fetchStations } from '../../api/stations'
import { fetchTrains, type Train } from '../../api/trains'
import {
  fetchSchedules,
  fetchSchedule,
  createSchedule,
  updateSchedule,
  updateScheduleStationByStation,
  type Schedule,
} from '../../api/schedules'

type Direction = 'asc' | 'desc'

type ScheduleRow = {
  scheduleStationId?: number
  station_id: number
  station_name: string
  sequence: number
  arrival_time: string
  departure_time: string
}

const router = useRouter()
const loading = ref(true)
const error = ref('')
const message = ref('')
const schedulesLoading = ref(false)
const savingSchedule = ref(false)
const schedules = ref<Schedule[]>([])
const routes = ref<Route[]>([])
const trains = ref<Train[]>([])
const stationLookup = ref<Map<number, { id: number; name: string }>>(new Map())
const scheduleDialogVisible = ref(false)
const viewDialogVisible = ref(false)
const editingScheduleId = ref<number | null>(null)
const viewingSchedule = ref<Schedule | null>(null)
const validationError = ref('')
const selectedDirection = ref<Direction>('asc')
const scheduleForm = ref({
  train_id: 0,
  route_id: 0,
  departure_time: '',
  is_active: true,
})
const stationRows = ref<ScheduleRow[]>([])

const hasSchedules = computed(() => schedules.value.length > 0)
const selectedRoute = computed(() => routes.value.find((route) => route.id === scheduleForm.value.route_id) ?? null)
const routeOptions = computed(() => routes.value.map((route) => ({ label: route.name, value: route.id })))
const trainOptions = computed(() => trains.value.map((train) => ({ label: `${train.train_number} - ${train.train_name}`, value: train.id })))

function formatTime(value?: string | null) {
  return value || '-'
}

function getRouteStations(route: Route | null) {
  return (route?.stations ?? [])
    .slice()
    .sort((a, b) => a.sequence - b.sequence)
    .map((station) => ({
      id: station.station_id,
      name: station.station?.name ?? stationLookup.value.get(station.station_id)?.name ?? 'Unknown station',
      sequence: station.sequence,
    }))
}

function syncStationRowsFromRoute() {
  const orderedStations = getRouteStations(selectedRoute.value)
  const ordered = selectedDirection.value === 'desc' ? orderedStations.reverse() : orderedStations

  const existingByStation = new Map(
    stationRows.value.map((row) => [row.station_id, row]),
  )

  stationRows.value = ordered.map((station, index) => {
    const existing = existingByStation.get(station.id)
    return {
      scheduleStationId: existing?.scheduleStationId,
      station_id: station.id,
      station_name: station.name,
      sequence: index + 1,
      arrival_time: existing?.arrival_time ?? '',
      departure_time: existing?.departure_time ?? '',
    }
  })
}

function resetForm() {
  editingScheduleId.value = null
  validationError.value = ''
  selectedDirection.value = 'asc'
  scheduleForm.value = {
    train_id: trains.value[0]?.id ?? 0,
    route_id: routes.value[0]?.id ?? 0,
    departure_time: '',
    is_active: true,
  }
  stationRows.value = []
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

async function loadLookups() {
  const [routesRes, trainsRes, stationsRes] = await Promise.all([fetchRoutes(), fetchTrains(), fetchStations()])
  routes.value = routesRes.routes
  trains.value = trainsRes.trains
  stationLookup.value = new Map(stationsRes.stations.map((station) => [station.id, { id: station.id, name: station.name }]))
}

async function loadSchedules() {
  schedulesLoading.value = true
  try {
    const res = await fetchSchedules()
    schedules.value = res.schedules
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load schedules'
  } finally {
    schedulesLoading.value = false
  }
}

function closeScheduleDialog() {
  scheduleDialogVisible.value = false
  resetSaveState()
}

function resetSaveState() {
  editingScheduleId.value = null
  stationRows.value = []
  validationError.value = ''
}

function openAddDialog() {
  resetForm()
  scheduleDialogVisible.value = true
  syncStationRowsFromRoute()
}

async function openEditDialog(schedule: Schedule) {
  try {
    const res = await fetchSchedule(schedule.id)
    const fullSchedule = res.schedule
    editingScheduleId.value = fullSchedule.id
    scheduleForm.value = {
      train_id: fullSchedule.train_id,
      route_id: fullSchedule.route_id,
      departure_time: fullSchedule.departure_time ?? '',
      is_active: fullSchedule.is_active,
    }
    selectedDirection.value = 'asc'

    const routeStations = getRouteStations(routes.value.find((route) => route.id === fullSchedule.route_id) ?? null)
    const scheduleStations = (fullSchedule.station_schedules ?? []).slice().sort((a, b) => a.sequence - b.sequence)
    const scheduleStationMap = new Map(scheduleStations.map((station) => [station.station_id, station]))

    stationRows.value = routeStations.map((station, index) => {
      const existing = scheduleStationMap.get(station.id)
      return {
        scheduleStationId: existing?.id,
        station_id: station.id,
        station_name: station.name,
        sequence: index + 1,
        arrival_time: existing?.arrival_time ?? '',
        departure_time: existing?.departure_time ?? '',
      }
    })

    scheduleDialogVisible.value = true
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load schedule details'
  }
}

async function viewSchedule(schedule: Schedule) {
  try {
    const res = await fetchSchedule(schedule.id)
    viewingSchedule.value = res.schedule
    viewDialogVisible.value = true
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load schedule details'
  }
}

async function saveSchedule() {
  validationError.value = ''

  if (!scheduleForm.value.train_id || !scheduleForm.value.route_id) {
    validationError.value = 'Route and train are required.'
    return
  }

  if (!stationRows.value.length) {
    validationError.value = 'Selected route does not have any stations.'
    return
  }

  savingSchedule.value = true

  try {
    const payload = {
      train_id: scheduleForm.value.train_id,
      route_id: scheduleForm.value.route_id,
      departure_time: scheduleForm.value.departure_time || null,
      is_active: scheduleForm.value.is_active,
    }

    let scheduleId = editingScheduleId.value

    if (!scheduleId) {
      const created = await createSchedule(payload)
      scheduleId = created.schedule.id
      editingScheduleId.value = scheduleId
    } else {
      await updateSchedule(scheduleId, payload)
    }

    if (!scheduleId) {
      throw new Error('Unable to determine schedule id')
    }

    for (const row of stationRows.value) {
      const stationPayload = {
        schedule_id: scheduleId,
        station_id: row.station_id,
        sequence: row.sequence,
        arrival_time: row.arrival_time || null,
        departure_time: row.departure_time || null,
      }

      await updateScheduleStationByStation(scheduleId, row.station_id, stationPayload)
    }

    message.value = 'Schedule saved successfully.'
    resetSaveState()
    closeScheduleDialog()
    await loadSchedules()
  } catch (err) {
    validationError.value = err instanceof Error ? err.message : 'Unable to save schedule'
  } finally {
    savingSchedule.value = false
  }
}

watch(
  () => scheduleForm.value.route_id,
  () => {
    if (scheduleDialogVisible.value) syncStationRowsFromRoute()
  },
)

watch(selectedDirection, () => {
  if (scheduleDialogVisible.value) syncStationRowsFromRoute()
})

watch(
  () => scheduleForm.value.train_id,
  (value) => {
    if (!editingScheduleId.value && !value && trains.value.length > 0) {
      scheduleForm.value.train_id = trains.value[0].id
    }
  },
)

onMounted(async () => {
  try {
    await Promise.all([loadUser(), loadLookups(), loadSchedules()])
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to initialize schedules page'
  }
})
</script>

<template>
  <main class="dashboard-shell">
    <Card class="dashboard-card shadow-sm">
      <template #content>
        <div class="dashboard-content">
          <p v-if="loading" class="text-light-emphasis">Loading your account...</p>

          <div v-else class="schedule-page">
            <div class="schedule-header">
              <div>
                <h1 class="schedule-title">Schedule Management</h1>
                <p class="schedule-subtitle">Create schedules, assign trains and routes, and manage station timings.</p>
              </div>

              <Button label="Add Schedule" icon="pi pi-plus" class="schedule-add-btn" @click="openAddDialog" />
            </div>

            <Message severity="success" class="mb-3" v-if="message">{{ message }}</Message>
            <Message v-if="error" severity="error" class="mb-3">{{ error }}</Message>

            <Card class="schedule-card">
              <template #content>
                <div v-if="schedulesLoading" class="schedule-empty-state">
                  <p>Loading schedules...</p>
                </div>

                <div v-else-if="!hasSchedules" class="schedule-empty-state">
                  <i class="pi pi-calendar schedule-empty-icon"></i>
                  <h2>No schedules yet</h2>
                  <p>Click Add Schedule to create the first trip schedule.</p>
                </div>

                <DataTable v-else :value="schedules" stripedRows responsiveLayout="scroll" class="schedule-table">
                  <Column field="id" header="ID" style="width: 90px" />
                  <Column header="Train">
                    <template #body="slotProps">
                      {{ slotProps.data.train?.train_number ?? slotProps.data.train_id }} - {{ slotProps.data.train?.train_name ?? 'Unknown' }}
                    </template>
                  </Column>
                  <Column header="Route">
                    <template #body="slotProps">
                      {{ slotProps.data.route?.name ?? slotProps.data.route_id }}
                    </template>
                  </Column>
                  <Column header="Departure">
                    <template #body="slotProps">
                      {{ formatTime(slotProps.data.departure_time) }}
                    </template>
                  </Column>
                  <Column header="Active" style="width: 120px">
                    <template #body="slotProps">
                      <span :class="slotProps.data.is_active ? 'status-pill status-on' : 'status-pill status-off'">
                        {{ slotProps.data.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </template>
                  </Column>
                  <Column header="Actions" style="width: 240px">
                    <template #body="slotProps">
                      <div class="schedule-actions">
                        <Button icon="pi pi-eye" aria-label="View schedule" severity="secondary" text rounded @click="viewSchedule(slotProps.data)" />
                        <Button icon="pi pi-pencil" aria-label="Edit schedule" severity="info" text rounded @click="openEditDialog(slotProps.data)" />
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

    <Dialog v-model:visible="scheduleDialogVisible" modal :header="editingScheduleId ? 'Edit Schedule' : 'Add Schedule'" :style="{ width: 'min(96vw, 980px)' }" :draggable="false">
      <div class="schedule-form">
        <Message v-if="validationError" severity="error" class="mb-3">{{ validationError }}</Message>

        <div class="schedule-form-grid">
          <div>
            <label class="form-label">Train</label>
            <Dropdown v-model="scheduleForm.train_id" :options="trainOptions" optionLabel="label" optionValue="value" placeholder="Select train" class="w-100" />
          </div>
          <div>
            <label class="form-label">Route</label>
            <Dropdown v-model="scheduleForm.route_id" :options="routeOptions" optionLabel="label" optionValue="value" placeholder="Select route" class="w-100" />
          </div>
          <div>
            <label class="form-label">Departure Time</label>
            <InputText v-model="scheduleForm.departure_time" class="w-100" placeholder="20:00:00" />
          </div>
          <div class="schedule-switch-row">
            <InputSwitch v-model="scheduleForm.is_active" />
            <div>
              <div class="schedule-switch-label">Active</div>
              <small class="text-muted">Enable the schedule for use in the system.</small>
            </div>
          </div>
        </div>

        <div class="schedule-direction-row">
          <label class="form-label mb-0">Direction</label>
          <div class="direction-toggle">
            <Button :severity="selectedDirection === 'asc' ? 'primary' : 'secondary'" label="Forward" @click="selectedDirection = 'asc'" />
            <Button :severity="selectedDirection === 'desc' ? 'primary' : 'secondary'" label="Return" @click="selectedDirection = 'desc'" />
          </div>
        </div>

        <div class="schedule-station-panel">
          <div class="schedule-station-panel-header">
            <h3>Station Timings</h3>
            <p class="text-muted">{{ selectedRoute?.name ?? 'Select a route to load stations' }}</p>
          </div>

          <DataTable :value="stationRows" responsiveLayout="scroll" class="schedule-station-table">
            <Column header="#" style="width: 80px">
              <template #body="slotProps">
                {{ slotProps.data.sequence }}
              </template>
            </Column>
            <Column header="Station">
              <template #body="slotProps">
                {{ slotProps.data.station_name }}
              </template>
            </Column>
            <Column header="Arrival">
              <template #body="slotProps">
                <InputText v-model="slotProps.data.arrival_time" placeholder="20:30:00" />
              </template>
            </Column>
            <Column header="Departure">
              <template #body="slotProps">
                <InputText v-model="slotProps.data.departure_time" placeholder="20:35:00" />
              </template>
            </Column>
          </DataTable>
        </div>

        <form class="schedule-dialog-actions" @submit.prevent="saveSchedule">
          <Button type="button" label="Cancel" severity="secondary" text @click="closeScheduleDialog" />
          <Button type="submit" :label="editingScheduleId ? 'Update Schedule' : 'Save Schedule'" icon="pi pi-check" :loading="savingSchedule" />
        </form>
      </div>
    </Dialog>

    <Dialog v-model:visible="viewDialogVisible" modal header="Schedule details" :style="{ width: 'min(96vw, 900px)' }" :draggable="false">
      <div class="schedule-view" v-if="viewingSchedule">
        <h3 class="mb-2">Trip summary</h3>
        <p><strong>Train:</strong> {{ viewingSchedule.train?.train_number ?? viewingSchedule.train_id }} - {{ viewingSchedule.train?.train_name ?? 'Unknown' }}</p>
        <p><strong>Route:</strong> {{ viewingSchedule.route?.name ?? viewingSchedule.route_id }}</p>
        <p><strong>Departure:</strong> {{ formatTime(viewingSchedule.departure_time) }}</p>
        <p><strong>Status:</strong> {{ viewingSchedule.is_active ? 'Active' : 'Inactive' }}</p>

        <div class="mt-4">
          <h4>Station timeline</h4>
          <DataTable :value="(viewingSchedule.station_schedules ?? []).slice().sort((a, b) => a.sequence - b.sequence)" responsiveLayout="scroll">
            <Column field="sequence" header="#" style="width: 80px" />
            <Column header="Station">
              <template #body="slotProps">
                {{ slotProps.data.station?.name ?? 'Unknown station' }}
              </template>
            </Column>
            <Column header="Arrival">
              <template #body="slotProps">
                {{ formatTime(slotProps.data.arrival_time) }}
              </template>
            </Column>
            <Column header="Departure">
              <template #body="slotProps">
                {{ formatTime(slotProps.data.departure_time) }}
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </Dialog>
  </main>
</template>

<style scoped>
.schedule-page { width: 100%; max-width: 1400px; margin: 0 auto }
.schedule-header { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1.5rem }
.schedule-title { margin:0; font-size:2rem; font-weight:700 }
.schedule-subtitle { margin:0.35rem 0 0; color:var(--text-secondary) }
.schedule-add-btn { min-width:140px }
.schedule-card { border-radius:20px; border:1px solid var(--border-light) }
.schedule-empty-state { text-align:center; padding:3rem 1rem }
.schedule-empty-icon { font-size:2.5rem; color:var(--accent-primary) }
.schedule-actions { display:flex; gap:0.5rem }
.schedule-form-grid { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem }
.schedule-switch-row { display:flex; align-items:center; gap:0.75rem; padding-top:1.5rem }
.schedule-switch-label { font-weight:600 }
.schedule-direction-row { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin:1.5rem 0 }
.direction-toggle { display:flex; gap:0.5rem }
.schedule-station-panel { border:1px solid var(--border-light); border-radius:16px; padding:1rem }
.schedule-station-panel-header { display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; margin-bottom:1rem }
.schedule-dialog-actions { display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem }
@media (max-width: 768px) {
  .schedule-form-grid { grid-template-columns: 1fr }
  .schedule-header, .schedule-direction-row, .schedule-station-panel-header { flex-direction:column; align-items:flex-start }
}
</style>
