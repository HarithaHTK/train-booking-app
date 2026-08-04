<template>
  <div class="app-container">
    <nav class="app-navbar">
      <div class="navbar-content">
        <router-link :to="{ name: 'auth-login' }" class="navbar-title">admin - Train Booking</router-link>

        <router-link
          v-if="isAuthenticatedRoute"
          :to="{ name: 'stations' }"
          class="navbar-link"
          :class="{ active: route.name === 'stations' }"
        >
          Stations
        </router-link>

        <router-link
          v-if="isAuthenticatedRoute"
          :to="{ name: 'routes' }"
          class="navbar-link"
          :class="{ active: route.name === 'routes' }"
        >
          Routes
        </router-link>

        <router-link
          v-if="isAuthenticatedRoute"
          :to="{ name: 'trains' }"
          class="navbar-link"
          :class="{ active: route.name === 'trains' }"
        >
          Trains
        </router-link>

        <router-link
          v-if="isAuthenticatedRoute"
          :to="{ name: 'schedules' }"
          class="navbar-link"
          :class="{ active: route.name === 'schedules' }"
        >
          Schedules
        </router-link>

        <div class="navbar-right">
          <ThemeToggle />
          <AccountMenu v-if="isAuthenticatedRoute" />
        </div>
      </div>
    </nav>
    <main class="app-main">
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import ThemeToggle from './components/ThemeToggle.vue'
import AccountMenu from './components/AccountMenu.vue'

const route = useRoute()

const isAuthenticatedRoute = computed(() => route.meta.requiresAuth === true)
</script>

<style scoped>
.app-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--bg-primary);
  transition: background-color 0.3s ease;
}

.app-navbar {
  background: var(--panel-bg);
  border-bottom: 1px solid var(--border-color);
  padding: 1rem 2rem;
  position: sticky;
  top: 0;
  z-index: 100;
  transition: all 0.3s ease;
}

.navbar-content {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.navbar-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  text-decoration: none;
}

.navbar-link {
  margin-left: 1rem;
  padding: 0.5rem 0.9rem;
  border-radius: 999px;
  color: var(--text-secondary);
  text-decoration: none;
  transition: all 0.2s ease;
}

.navbar-link:hover,
.navbar-link.active {
  color: var(--text-primary);
  background: var(--accent-light);
}

.navbar-right {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-left: auto;
}

.app-main {
  flex: 1;
  padding: 2rem;
  background: var(--bg-primary);
  transition: background-color 0.3s ease;
}

@media (max-width: 768px) {
  .app-navbar {
    padding: 1rem;
  }

  .navbar-content {
    gap: 1rem;
  }

  .navbar-title {
    font-size: 1.25rem;
  }

  .app-main {
    padding: 1rem;
  }
}
</style>
