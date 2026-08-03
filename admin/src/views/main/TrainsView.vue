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
import MultiSelect from 'primevue/multiselect'
import Message from 'primevue/message'

import { fetchCurrentUser } from '../../api/auth'
import {
  fetchTrains,
  createTrain,
  updateTrain,
  deleteTrain,
  fetchTrain,
  type Train,
  type TrainPayload,
} from '../../api/trains'
import { fetchEngines, type Engine } from '../../api/engines'
import { fetchCoaches, type Coach } from '../../api/coaches'

const router = useRouter()
const message = ref('')
const error = ref('')
const loading = ref(true)
const trains = ref<Train[]>([])
const trainsLoading = ref(false)
const saving = ref(false)
const rowActionLoadingId = ref<number | null>(null)

const engines = ref<Engine[]>([])
const coaches = ref<Coach[]>([])

const dialogVisible = ref(false)
const viewDialogVisible = ref(false)
const editingId = ref<number | null>(null)

const form = ref<TrainPayload>({
  train_number: '',
  train_name: '',
  is_active: true,
  engine_ids: [],
  coach_ids: [],
})

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

async function loadLists() {
  try {
    const [eResp, cResp] = await Promise.all([fetchEngines(), fetchCoaches()])
    engines.value = eResp.engines || []
    coaches.value = cResp.coaches || []
  } catch (err) {
    console.error(err)
  }
}

async function loadTrains() {
  trainsLoading.value = true
  try {
    const resp = await fetchTrains()
    trains.value = resp.trains
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load trains'
  } finally {
    trainsLoading.value = false
  }
}

function resetForm() {
  form.value = {
    train_number: '',
    train_name: '',
    is_active: true,
    engine_ids: [],
    coach_ids: [],
  }
  editingId.value = null
}

function openAdd() {
  resetForm()
  dialogVisible.value = true
}

async function openEdit(train: Train) {
  try {
    const resp = await fetchTrain(train.id)
    const t = resp.train
    form.value = {
      train_number: t.train_number || '',
      train_name: t.train_name || '',
      is_active: t.is_active ?? true,
      engine_ids: (t.engines || []).map((e: any) => e.id),
      coach_ids: (t.coaches || []).map(c => c.id),
    }
    editingId.value = t.id
    dialogVisible.value = true
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load train'
  }
}

async function save() {
  // minimal validation
  if (!form.value.train_number.trim() || !form.value.train_name.trim()) {
    error.value = 'Train number and name are required.'
    return
  }

  saving.value = true
  error.value = ''

  try {
    const payload = {
      train_number: form.value.train_number.trim(),
      train_name: form.value.train_name.trim(),
      is_active: form.value.is_active,
      engine_ids: form.value.engine_ids || [],
      coach_ids: form.value.coach_ids || [],
    }

    if (editingId.value) {
      await updateTrain(editingId.value, payload)
      message.value = 'Train updated successfully.'
    } else {
      await createTrain(payload)
      message.value = 'Train created successfully.'
    }

    dialogVisible.value = false
    await loadTrains()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to save train'
  } finally {
    saving.value = false
  }
}

async function removeTrain(t: Train) {
  const confirmed = window.confirm(`Delete train "${t.train_number}"? This cannot be undone.`)
  if (!confirmed) return
  rowActionLoadingId.value = t.id
  try {
    await deleteTrain(t.id)
    message.value = 'Train deleted successfully.'
    await loadTrains()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to delete train'
  } finally {
    rowActionLoadingId.value = null
  }
}

async function viewTrain(t: Train) {
  try {
    const resp = await fetchTrain(t.id)
    // replace selected train in dialog state for display
    const tr = resp.train
    // build a small display object into message for simplicity
    viewDetails.value = tr
    viewDialogVisible.value = true
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Unable to load train'
  }
}

const viewDetails = ref<Train | null>(null)

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
  Promise.all([loadUser(), loadLists(), loadTrains()])
})

const hasTrains = computed(() => trains.value.length > 0)

const viewEngineNames = computed(() => (viewDetails.value?.engines || []).map((engine: any) => engine?.name).filter(Boolean))
const viewCoachRows = computed(() => viewDetails.value?.coaches || [])
const viewTotalCoaches = computed(() => viewCoachRows.value.length)
const viewTotalSeats = computed(() => viewCoachRows.value.reduce((sum: number, coach: any) => sum + (coach.seat_count || 0), 0))
</script>

<template>
  <main class="dashboard-shell">
    <Card class="dashboard-card shadow-sm">
      <template #content>
        <div class="dashboard-content">
          <p v-if="loading" class="text-light-emphasis">Loading your account...</p>

          <div v-else class="train-page">
            <div class="train-header">
              <div>
                <h1 class="train-title">Train Management</h1>
                <p class="train-subtitle">Review and manage trains and their coaches.</p>
              </div>

              <Button label="Add Train" icon="pi pi-plus" class="train-add-btn" @click="openAdd" />
            </div>

            <Message severity="success" class="mb-3" v-if="message">{{ message }}</Message>
            <Message v-if="error" severity="error" class="mb-3">{{ error }}</Message>

            <Card class="train-card">
              <template #content>
                <div v-if="trainsLoading" class="train-empty-state">
                  <p>Loading trains...</p>
                </div>

                <div v-else-if="!hasTrains" class="train-empty-state">
                  <i class="pi pi-train train-empty-icon"></i>
                  <h2>No trains yet</h2>
                  <p>Click Add Train to create the first train entry.</p>
                </div>

                <DataTable v-else :value="trains" stripedRows responsiveLayout="scroll" class="train-table">
                  <Column field="id" header="ID" style="width: 90px" />
                  <Column field="train_number" header="Number" />
                  <Column field="train_name" header="Name" />
                  <!-- Route removed: trains no longer require a route -->
                  <Column header="Total Seats">
                    <template #body="slotProps">
                      {{ (slotProps.data.coaches || []).reduce((sum: number, c: any) => sum + (c.seat_count || 0), 0) }}
                    </template>
                  </Column>
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
                  <Column header="Actions" style="width: 240px">
                    <template #body="slotProps">
                      <div class="train-actions">
                        <Button icon="pi pi-eye" aria-label="View" severity="secondary" text rounded @click="viewTrain(slotProps.data)" />
                        <Button icon="pi pi-pencil" aria-label="Edit" severity="info" text rounded @click="openEdit(slotProps.data)" />
                        <Button icon="pi pi-trash" aria-label="Delete" severity="danger" text rounded :loading="rowActionLoadingId === slotProps.data.id" @click="removeTrain(slotProps.data)" />
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

    <Dialog v-model:visible="dialogVisible" modal :header="editingId ? 'Edit Train' : 'Add Train'" :style="{ width: 'min(92vw, 720px)' }" :draggable="false">
      <div class="train-form">
        <Message v-if="error" severity="error" class="mb-3">{{ error }}</Message>

        <div class="mb-3">
          <label class="form-label">Train Number</label>
          <InputText v-model="form.train_number" class="w-100" placeholder="T123" />
        </div>

        <div class="mb-3">
          <label class="form-label">Train Name</label>
          <InputText v-model="form.train_name" class="w-100" placeholder="Express 1" />
        </div>

        <div class="mb-3">
          <label class="form-label">Engines</label>
          <MultiSelect v-model="form.engine_ids" :options="engines" optionLabel="name" optionValue="id" placeholder="Select engines" class="w-100" />
        </div>

        <div class="mb-3">
          <label class="form-label">Reserved Coaches</label>
          <MultiSelect v-model="form.coach_ids" :options="coaches.filter(c => c.type === 'reserved')" optionLabel="name" optionValue="id" placeholder="Select reserved coaches" class="w-100" />
        </div>

        <div class="mb-3">
          <label class="form-label">Unreserved Coaches</label>
          <MultiSelect v-model="form.coach_ids" :options="coaches.filter(c => c.type !== 'reserved')" optionLabel="name" optionValue="id" placeholder="Select unreserved coaches" class="w-100" />
        </div>

        <div class="mb-4 train-switch-row">
          <InputSwitch v-model="form.is_active" />
          <div>
            <div class="train-switch-label">Active</div>
            <small class="text-muted">Enable the train for scheduling and bookings.</small>
          </div>
        </div>

        <div class="train-dialog-actions">
          <Button label="Cancel" severity="secondary" text @click="dialogVisible = false" />
          <Button :label="editingId ? 'Update Train' : 'Save Train'" icon="pi pi-check" :loading="saving" @click="save" />
        </div>
      </div>
    </Dialog>

    <Dialog v-model:visible="viewDialogVisible" modal header="Train Details" :style="{ width: 'min(92vw, 640px)' }">
      <div v-if="viewDetails" class="train-summary">
        <div class="train-summary-header">
          <div>
            <h3 class="train-summary-title">{{ viewDetails.train_number }} — {{ viewDetails.train_name }}</h3>
            <span :class="viewDetails.is_active ? 'status-pill status-on' : 'status-pill status-off'">
              {{ viewDetails.is_active ? 'Active' : 'Inactive' }}
            </span>
          </div>
        </div>

        <div class="train-summary-grid">
          <div class="summary-card">
            <div class="summary-label">Assigned Engines</div>
            <div class="summary-value">
              <template v-if="viewEngineNames.length">
                {{ viewEngineNames.join(', ') }}
              </template>
              <template v-else>-</template>
            </div>
          </div>

          <div class="summary-card">
            <div class="summary-label">Plan</div>
            <div class="summary-value">{{ viewDetails.plan ?? '-' }}</div>
          </div>

          <div class="summary-card">
            <div class="summary-label">Total Coaches</div>
            <div class="summary-value">{{ viewTotalCoaches }}</div>
          </div>

          <div class="summary-card">
            <div class="summary-label">Total Seats</div>
            <div class="summary-value">{{ viewTotalSeats }}</div>
          </div>
        </div>

        <div class="train-summary-section">
          <h4 class="train-summary-section-title">Seat Breakdown by Coach</h4>
          <DataTable :value="viewCoachRows" stripedRows responsiveLayout="scroll" class="train-summary-table">
            <Column field="name" header="Coach Name" />
            <Column field="type" header="Type" />
            <Column header="Seat Count">
              <template #body="slotProps">
                {{ slotProps.data.seat_count || 0 }}
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </Dialog>
  </main>
</template>

<style scoped>
.train-page {
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
}
.train-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}
.train-title { margin: 0; font-size: 2rem; font-weight: 700 }
.train-subtitle { margin: 0.35rem 0 0; color: var(--text-secondary) }
.train-add-btn { min-width: 140px }
.train-card { border-radius: 20px; border: 1px solid var(--border-light) }
.train-empty-state { padding: 3rem 1rem; text-align: center; color: var(--text-secondary) }
.train-empty-icon { font-size: 2.5rem; color: var(--accent-primary); margin-bottom: 0.75rem }
.train-table { width: 100% }
.train-actions { display: flex; gap: 0.35rem }
.train-form { display: flex; flex-direction: column }
.train-switch-row { display: flex; align-items: center; gap: 0.85rem }
.train-switch-label { font-weight: 600; color: var(--text-primary) }
.train-dialog-actions { display: flex; justify-content: flex-end; gap: 0.75rem }
.train-dialog-actions :deep(.p-button) { min-width: 120px }

.train-summary {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.train-summary-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.train-summary-title {
  margin: 0 0 0.5rem;
  font-size: 1.35rem;
  font-weight: 700;
}

.train-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem;
}

.summary-card {
  border: 1px solid var(--border-light);
  border-radius: 14px;
  padding: 1rem;
  background: var(--surface-card);
}

.summary-label {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin-bottom: 0.35rem;
}

.summary-value {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-primary);
  word-break: break-word;
}

.train-summary-section {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.train-summary-section-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
}

.train-summary-table {
  width: 100%;
}

@media (max-width: 768px) {
  .train-dialog-actions { flex-direction: column }
  .train-dialog-actions :deep(.p-button) { width: 100% }
  .train-summary-grid {
    grid-template-columns: 1fr;
  }
  .train-summary-header {
    flex-direction: column;
  }
}
</style>
