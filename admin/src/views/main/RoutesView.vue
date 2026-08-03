<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputSwitch from 'primevue/inputswitch'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import { fetchCurrentUser } from '../../api/auth'
import {
  fetchRoutes,
  createRoute,
  updateRoute,
  deleteRoute,
  fetchRoute,
  type Route,
  type RoutePayload,
} from '../../api/routes'
import { fetchStations } from '../../api/stations'
import RouteStationsEditor from '../../components/RouteStationsEditor.vue'

const router = useRouter()
const message = ref('')
const error = ref('')
const loading = ref(true)
const routes = ref<Route[]>([])
const routesLoading = ref(false)
const savingRoute = ref(false)
const rowActionLoadingId = ref<number | null>(null)
const routeDialogVisible = ref(false)
const viewDialogVisible = ref(false)
const routeForm = ref<RoutePayload>({ name: '', description: '', is_active: true })
const routeStationsSelection = ref<number[]>([])
const availableStations = ref<{ id: number; name: string }[]>([])
const validationError = ref('')
const editingRouteId = ref<number | null>(null)
const viewingRoute = ref<Route | null>(null)

function resetRouteForm() {
  routeForm.value = { name: '', description: '', is_active: true }
  routeStationsSelection.value = []
  validationError.value = ''
  editingRouteId.value = null
}

async function openRouteDialog() {
  resetRouteForm()
  await loadStationsList()
  routeDialogVisible.value = true
}

async function openEditDialog(route: Route) {
  try {
    // Fetch full route data with stations relation
    const res = await fetchRoute(route.id)
    const fullRoute = res.route
    
    editingRouteId.value = fullRoute.id
    routeForm.value = { name: fullRoute.name, description: fullRoute.description ?? '', is_active: fullRoute.is_active }
    
    // Load stations first
    await loadStationsList()
    
    // Merge route's station data into availableStations to ensure lookup works
    const stationMap = new Map(availableStations.value.map(s => [s.id, s]))
    for (const rs of fullRoute.stations ?? []) {
      if (rs.station && !stationMap.has(rs.station_id)) {
        stationMap.set(rs.station_id, { id: rs.station_id, name: rs.station.name })
      }
    }
    availableStations.value = Array.from(stationMap.values())
    
    // preload stations selection by their station_id in sequence order
    routeStationsSelection.value = (fullRoute.stations ?? []).slice().sort((a, b) => a.sequence - b.sequence).map(s => s.station_id)
    routeDialogVisible.value = true
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load route for editing'
  }
}

function closeRouteDialog() { routeDialogVisible.value = false }

async function loadStationsList() {
  try {
    const res = await fetchStations()
    availableStations.value = res.stations.map(s => ({ id: s.id, name: s.name }))
  } catch (err) {
    // ignore
  }
}

async function loadRoutes() {
  routesLoading.value = true
  try {
    const res = await fetchRoutes()
    routes.value = res.routes
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load routes'
  } finally {
    routesLoading.value = false
  }
}

async function saveRoute() {
  validationError.value = ''

  if (!routeForm.value.name.trim()) {
    validationError.value = 'Name is required.'
    return
  }

  savingRoute.value = true

  try {
    const payload: RoutePayload = {
      name: routeForm.value.name.trim(),
      description: routeForm.value.description?.trim(),
      is_active: routeForm.value.is_active,
    }

    let createdRoute: Route

    if (editingRouteId.value) {
      const res = await updateRoute(editingRouteId.value, payload)
      createdRoute = res.route
      message.value = 'Route updated successfully.'
    } else {
      const res = await createRoute(payload)
      createdRoute = res.route
      message.value = 'Route created successfully.'
    }

    // sync route stations via API: simple approach = delete existing and recreate
    // but since backend has route-stations CRUD, for simplicity here we will recreate
    // Note: implement proper diffs if needed later
    // First, fetch route to get current route stations
    const fetched = await fetchRoute(createdRoute.id)
    const existing = fetched.route.stations ?? []

    // delete all existing route stations
    for (const rs of existing) {
      try {
        await fetch(`/api/route-stations/${rs.id}`, { method: 'DELETE', headers: { Authorization: `Bearer ${localStorage.getItem('admin_auth_token')}` } })
      } catch {}
    }

    // create new route stations in order
    for (let i = 0; i < routeStationsSelection.value.length; i++) {
      const stationId = routeStationsSelection.value[i]
      await fetch('/api/route-stations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${localStorage.getItem('admin_auth_token')}` },
        body: JSON.stringify({ route_id: createdRoute.id, station_id: stationId, sequence: i + 1 }),
      })
    }

    await loadRoutes()
    closeRouteDialog()
  } catch (err) {
    validationError.value = err instanceof Error ? err.message : 'Unable to save route'
  } finally {
    savingRoute.value = false
  }
}

async function removeRoute(route: Route) {
  const confirmed = window.confirm(`Delete route "${route.name}"? This cannot be undone.`)
  if (!confirmed) return

  rowActionLoadingId.value = route.id
  try {
    await deleteRoute(route.id)
    message.value = 'Route deleted successfully.'
    await loadRoutes()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to delete route'
  } finally {
    rowActionLoadingId.value = null
  }
}

async function viewRoute(route: Route) {
  try {
    const res = await fetchRoute(route.id)
    viewingRoute.value = res.route
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load route details'
    return
  }
  viewDialogVisible.value = true
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

onMounted(() => {
  Promise.all([loadUser(), loadRoutes(), loadStationsList()])
})

const hasRoutes = computed(() => routes.value.length > 0)


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
                <h1 class="route-title">Route Management</h1>
                <p class="route-subtitle">Create and review routes and their station sequences.</p>
              </div>

              <Button label="Add Route" icon="pi pi-plus" class="route-add-btn" @click="openRouteDialog" />
            </div>

            <Message severity="success" class="mb-3" v-if="message">{{ message }}</Message>
            <Message v-if="error" severity="error" class="mb-3">{{ error }}</Message>

            <Card class="route-card">
              <template #content>
                <div v-if="routesLoading" class="route-empty-state">
                  <p>Loading routes...</p>
                </div>

                <div v-else-if="!hasRoutes" class="route-empty-state">
                  <i class="pi pi-route route-empty-icon"></i>
                  <h2>No routes yet</h2>
                  <p>Click Add Route to create the first route.</p>
                </div>

                <DataTable v-else :value="routes" stripedRows responsiveLayout="scroll" class="route-table">
                  <Column field="id" header="ID" style="width: 90px" />
                  <Column field="name" header="Name" />
                  <Column field="description" header="Description" />
                  <Column header="Active" style="width: 120px">
                    <template #body="slotProps">
                      <span :class="slotProps.data.is_active ? 'status-pill status-on' : 'status-pill status-off'">
                        {{ slotProps.data.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </template>
                  </Column>
                  <Column header="Actions" style="width: 300px">
                    <template #body="slotProps">
                      <div class="route-actions">
                        <Button icon="pi pi-eye" aria-label="View route" severity="secondary" text rounded @click="viewRoute(slotProps.data)" />
                        <Button icon="pi pi-pencil" aria-label="Edit route" severity="info" text rounded @click="openEditDialog(slotProps.data)" />
                        <Button icon="pi pi-trash" aria-label="Delete route" severity="danger" text rounded :loading="rowActionLoadingId === slotProps.data.id" @click="removeRoute(slotProps.data)" />
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

    <Dialog v-model:visible="routeDialogVisible" modal :header="editingRouteId ? 'Edit Route' : 'Add Route'" :style="{ width: 'min(92vw, 640px)' }" :draggable="false">
      <div class="route-form">
        <Message v-if="validationError" severity="error" class="mb-3">{{ validationError }}</Message>

        <div class="mb-3">
          <label class="form-label">Name</label>
          <InputText v-model="routeForm.name" class="w-100" placeholder="Main Line" />
        </div>

        <div class="mb-3">
          <label class="form-label">Description</label>
          <InputText v-model="routeForm.description" class="w-100" placeholder="Primary railway route line" />
        </div>

        <div class="mb-3">
          <RouteStationsEditor :available="availableStations" v-model="routeStationsSelection" />
        </div>

        <div class="mb-4 route-switch-row">
          <InputSwitch v-model="routeForm.is_active" />
          <div>
            <div class="route-switch-label">Active</div>
            <small class="text-muted">Enable the route for use in the system.</small>
          </div>
        </div>

        <div class="route-dialog-actions">
          <Button label="Cancel" severity="secondary" text @click="closeRouteDialog" />
          <Button :label="editingRouteId ? 'Update Route' : 'Save Route'" icon="pi pi-check" :loading="savingRoute" @click="saveRoute" />
        </div>
      </div>
    </Dialog>

    <Dialog v-model:visible="viewDialogVisible" modal header="Route details" :style="{ width: 'min(92vw, 640px)' }" :draggable="false">
      <div class="route-view">
        <div v-if="viewingRoute">
          <h3>{{ viewingRoute.name }}</h3>
          <p class="text-muted">{{ viewingRoute.description }}</p>

          <div class="mt-3">
            <h4>Stations (sequence)</h4>
            <ol>
              <li v-for="s in (viewingRoute.stations ?? []).sort((a,b)=>a.sequence-b.sequence)" :key="s.id">{{ s.station?.name ?? 'Unknown' }}</li>
            </ol>
          </div>
        </div>
      </div>
    </Dialog>
  </main>
</template>

<style scoped>
.route-page { width: 100%; max-width: 1400px; margin: 0 auto }
.route-header { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:1.5rem }
.route-title { margin:0; font-size:2rem; font-weight:700 }
.route-subtitle { margin:0.35rem 0 0; color:var(--text-secondary) }
.route-add-btn { min-width:140px }
.route-card { border-radius:20px; border:1px solid var(--border-light) }
.route-empty-icon { font-size:2.5rem; color:var(--accent-primary) }
.route-actions { display:flex; gap:0.5rem }
.route-dialog-actions { display:flex; justify-content:flex-end; gap:0.75rem }
</style>
