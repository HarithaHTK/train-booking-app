<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import MultiSelect from 'primevue/multiselect'
import Button from 'primevue/button'

const props = defineProps<{
  available: Array<{ id: number; name: string }>
  modelValue: number[]
}>()

const emit = defineEmits(['update:modelValue'])

const selected = ref<number[]>([])
let isInitializing = true

watch(
  () => props.modelValue,
  (v: number[]) => {
    selected.value = v ? [...v] : []
    isInitializing = false
  },
  { immediate: true },
)

watch(
  () => selected.value,
  (v: number[]) => {
    if (!isInitializing) {
      emit('update:modelValue', v)
    }
  },
  { deep: true },
)

function moveUp(index: number) {
  if (index <= 0) return
  const arr = [...selected.value]
  const tmp = arr[index - 1]
  arr[index - 1] = arr[index]
  arr[index] = tmp
  selected.value = arr
  emit('update:modelValue', selected.value)
}

function moveDown(index: number) {
  if (index >= selected.value.length - 1) return
  const arr = [...selected.value]
  const tmp = arr[index + 1]
  arr[index + 1] = arr[index]
  arr[index] = tmp
  selected.value = arr
  emit('update:modelValue', selected.value)
}

const selectedDetails = computed(() => {
  const map = new Map(props.available.map((s: any) => [s.id, s]))
  return selected.value.map((id) => map.get(id) || { id, name: 'Unknown' })
})
</script>

<template>
  <div>
    <label class="form-label">Add stations</label>
    <MultiSelect
      v-model="selected"
      :options="props.available"
      optionLabel="name"
      optionValue="id"
      display="chip"
      placeholder="Select stations"
      class="w-100 mb-3"
    />

    <div v-if="selectedDetails.length">
      <div v-for="(item, idx) in selectedDetails" :key="item.id" class="route-station-row mb-2 d-flex items-center">
        <div class="flex-1">{{ idx + 1 }}. {{ item.name }}</div>
        <div>
          <Button icon="pi pi-chevron-up" text rounded class="mr-2" @click="moveUp(idx)" />
          <Button icon="pi pi-chevron-down" text rounded @click="moveDown(idx)" />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.route-station-row { display:flex; justify-content:space-between; align-items:center }
</style>
