<template>
  <div class="theme-toggle-container">
    <button 
      class="theme-toggle-btn"
      @click="toggleTheme"
      :title="`Switch to ${currentTheme === 'dark' ? 'light' : 'dark'} mode`"
      aria-label="Toggle theme"
    >
      <i 
        :class="currentTheme === 'dark' ? 'pi pi-sun' : 'pi pi-moon'"
        class="theme-icon"
      ></i>
    </button>
  </div>
</template>

<script setup lang="ts">
import { useTheme } from '../composables/useTheme'

const { currentTheme, toggleTheme } = useTheme()
</script>

<style scoped>
.theme-toggle-container {
  display: flex;
  align-items: center;
  justify-content: center;
}

.theme-toggle-btn {
  background: var(--button-bg);
  border: 1px solid var(--border-color);
  color: var(--text-primary);
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  padding: 0;
  font-size: 18px;
}

.theme-toggle-btn:hover {
  background: var(--bg-tertiary);
  border-color: var(--accent-primary);
  color: var(--accent-primary);
}

.theme-toggle-btn:active {
  transform: scale(0.95);
}

.theme-icon {
  display: inline-block;
  width: 1em;
  height: 1em;
}
</style>
