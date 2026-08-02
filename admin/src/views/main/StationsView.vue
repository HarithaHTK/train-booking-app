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
  createStation,
  deleteStation,
  fetchStations,
  updateStation,
  type Station,
  type StationPayload,
} from '../../api/stations'

const router = useRouter()
const message = ref('')
const error = ref('')
const loading = ref(true)
const stations = ref<Station[]>([])
const stationsLoading = ref(false)
const savingStation = ref(false)
const rowActionLoadingId = ref<number | null>(null)
const stationDialogVisible = ref(false)
const stationForm = ref<StationPayload>({
  name: '',
  address: '',
  is_active: true,
})
const validationError = ref('')
const editingStationId = ref<number | null>(null)

const hasStations = computed(() => stations.value.length > 0)

function formatDateTime(value?: string) {
  if (!value) return '-'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value

  return new Intl.DateTimeFormat(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

function resetStationForm() {
  stationForm.value = {
    name: '',
    address: '',
    is_active: true,
  }
  validationError.value = ''
  editingStationId.value = null
}

function openStationDialog() {
  resetStationForm()
  stationDialogVisible.value = true
}

function openEditDialog(station: Station) {
  stationForm.value = {
    name: station.name,
    address: station.address,
    is_active: station.is_active,
  }
  editingStationId.value = station.id
  validationError.value = ''
  stationDialogVisible.value = true
}

function closeStationDialog() {
  stationDialogVisible.value = false
}

async function loadStations() {
  stationsLoading.value = true

  try {
    const response = await fetchStations()
    stations.value = response.stations
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load stations'
  } finally {
    stationsLoading.value = false
  }
}

async function saveStation() {
  validationError.value = ''

  if (!stationForm.value.name.trim() || !stationForm.value.address.trim()) {
    validationError.value = 'Name and address are required.'
    return
  }

  savingStation.value = true

  try {
    const payload = {
      name: stationForm.value.name.trim(),
      address: stationForm.value.address.trim(),
      is_active: stationForm.value.is_active,
    }

    if (editingStationId.value) {
      await updateStation(editingStationId.value, payload)
      message.value = 'Station updated successfully.'
    } else {
      await createStation(payload)
      message.value = 'Station created successfully.'
    }

    closeStationDialog()
    await loadStations()
  } catch (err) {
    validationError.value = err instanceof Error ? err.message : 'Unable to save station'
  } finally {
    savingStation.value = false
  }
}

async function toggleStationActive(station: Station) {
  rowActionLoadingId.value = station.id

  try {
    await updateStation(station.id, {
      is_active: !station.is_active,
    })

    message.value = `Station marked as ${!station.is_active ? 'active' : 'inactive'}.`
    await loadStations()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to update station'
  } finally {
    rowActionLoadingId.value = null
  }
}

async function removeStation(station: Station) {
  const confirmed = window.confirm(`Delete station "${station.name}"? This cannot be undone.`)
  if (!confirmed) return

  rowActionLoadingId.value = station.id

  try {
    await deleteStation(station.id)
    message.value = 'Station deleted successfully.'
    await loadStations()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to delete station'
  } finally {
    rowActionLoadingId.value = null
  }
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
  Promise.all([loadUser(), loadStations()])
})
</script>

<template>
  <main class="dashboard-shell">
    <Card class="dashboard-card shadow-sm">
      <template #content>
        <div class="dashboard-content">
          <p v-if="loading" class="text-light-emphasis">Loading your account...</p>

          <div v-else class="station-page">
            <div class="station-header">
              <div>
                <h1 class="station-title">Station Management</h1>
                <p class="station-subtitle">Create and review stations from one place.</p>
              </div>

              <Button
                label="Add Station"
                icon="pi pi-plus"
                class="station-add-btn"
                @click="openStationDialog"
              />
            </div>

            <Message severity="success" class="mb-3" v-if="message">{{ message }}</Message>
            <Message v-if="error" severity="error" class="mb-3">{{ error }}</Message>

            <Card class="station-card">
              <template #content>
                <div v-if="stationsLoading" class="station-empty-state">
                  <p>Loading stations...</p>
                </div>

                <div v-else-if="!hasStations" class="station-empty-state">
                  <i class="pi pi-map-marker station-empty-icon"></i>
                  <h2>No stations yet</h2>
                  <p>Click Add Station to create the first station entry.</p>
                </div>

                <DataTable v-else :value="stations" stripedRows responsiveLayout="scroll" class="station-table">
                  <Column field="id" header="ID" style="width: 90px" />
                  <Column field="name" header="Name" />
                  <Column field="address" header="Address" />
                  <Column header="Active" style="width: 120px">
                    <template #body="slotProps">
                      <span :class="slotProps.data.is_active ? 'status-pill status-on' : 'status-pill status-off'">
                        {{ slotProps.data.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </template>
                  </Column>
                  <Column header="Created At">
                    <template #body="slotProps">
                      {{ formatDateTime(slotProps.data.created_at) }}
                    </template>
                  </Column>
                  <Column header="Updated At">
                    <template #body="slotProps">
                      {{ formatDateTime(slotProps.data.updated_at) }}
                    </template>
                  </Column>
                      <Column header="Actions" style="width: 220px">
                        <template #body="slotProps">
                          <div class="station-actions">
                            <Button
                              :icon="slotProps.data.is_active ? 'pi pi-pause' : 'pi pi-play'"
                              :aria-label="slotProps.data.is_active ? 'Set inactive' : 'Set active'"
                              severity="secondary"
                              text
                              rounded
                              :loading="rowActionLoadingId === slotProps.data.id"
                              @click="toggleStationActive(slotProps.data)"
                            />
                            <Button
                              icon="pi pi-pencil"
                              aria-label="Edit station"
                              severity="info"
                              text
                              rounded
                              @click="openEditDialog(slotProps.data)"
                            />
                            <Button
                              icon="pi pi-trash"
                              aria-label="Delete station"
                              severity="danger"
                              text
                              rounded
                              :loading="rowActionLoadingId === slotProps.data.id"
                              @click="removeStation(slotProps.data)"
                            />
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

    <Dialog
      v-model:visible="stationDialogVisible"
      modal
      :header="editingStationId ? 'Edit Station' : 'Add Station'"
      :style="{ width: 'min(92vw, 560px)' }"
      :draggable="false"
    >
      <div class="station-form">
        <Message v-if="validationError" severity="error" class="mb-3">{{ validationError }}</Message>

        <div class="mb-3">
          <label class="form-label">Name</label>
          <InputText v-model="stationForm.name" class="w-100" placeholder="Central Station" />
        </div>

        <div class="mb-3">
          <label class="form-label">Address</label>
          <textarea
            v-model="stationForm.address"
            class="form-control"
            rows="4"
            placeholder="123 Main Street, City"
          ></textarea>
        </div>

        <div class="mb-4 station-switch-row">
          <InputSwitch v-model="stationForm.is_active" />
          <div>
            <div class="station-switch-label">Active</div>
            <small class="text-muted">Enable the station for use in the system.</small>
          </div>
        </div>

        <div class="station-dialog-actions">
          <Button label="Cancel" severity="secondary" text @click="closeStationDialog" />
          <Button
            :label="editingStationId ? 'Update Station' : 'Save Station'"
            icon="pi pi-check"
            :loading="savingStation"
            @click="saveStation"
          />
        </div>
      </div>
    </Dialog>
  </main>
</template>

<style scoped>
.dashboard-shell {
  min-height: 100vh;
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
  padding: 2rem;
}

.station-page {
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
}

.station-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.station-title {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
}

.station-subtitle {
  margin: 0.35rem 0 0;
  color: var(--text-secondary);
}

.station-add-btn {
  min-width: 140px;
}

.station-card {
  border-radius: 20px;
  border: 1px solid var(--border-light);
}

.station-empty-state {
  padding: 3rem 1rem;
  text-align: center;
  color: var(--text-secondary);
}

.station-empty-icon {
  font-size: 2.5rem;
  color: var(--accent-primary);
  margin-bottom: 0.75rem;
}

.station-empty-state h2 {
  margin: 0 0 0.5rem;
  color: var(--text-primary);
}

.station-table {
  width: 100%;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 78px;
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 600;
}

.status-on {
  background: rgba(34, 197, 94, 0.15);
  color: #16a34a;
}

.status-off {
  background: rgba(239, 68, 68, 0.15);
  color: #dc2626;
}

.station-form {
  display: flex;
  flex-direction: column;
}

.station-switch-row {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.station-switch-label {
  font-weight: 600;
  color: var(--text-primary);
}

.station-dialog-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.station-dialog-actions :deep(.p-button) {
  min-width: 120px;
}

.station-actions {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 0.35rem;
}

.station-actions :deep(.p-button) {
  min-width: 0;
}

@media (max-width: 768px) {
  .dashboard-content {
    padding: 1rem;
  }

  .station-title {
    font-size: 1.6rem;
  }

  .station-dialog-actions {
    flex-direction: column;
  }

  .station-dialog-actions :deep(.p-button) {
    width: 100%;
  }

  .station-actions {
    flex-direction: column;
    width: 100%;
  }

  .station-actions :deep(.p-button) {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
