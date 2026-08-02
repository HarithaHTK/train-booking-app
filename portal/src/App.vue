<template>
  <div class="app-container">
    <nav class="app-navbar">
      <div class="navbar-content">
        <router-link :to="{ name: 'home' }" class="navbar-title">Train Booking</router-link>

        <div class="navbar-right">
          <div v-if="showAuthActions" class="navbar-actions">
            <router-link :to="{ name: 'auth-login' }" class="nav-link-wrap">
              <Button label="Login" severity="secondary" text />
            </router-link>
            <router-link :to="{ name: 'auth-register' }" class="nav-link-wrap">
              <Button label="Register" />
            </router-link>
          </div>
          
          <ThemeToggle />
          <AccountMenu v-if="!showAuthActions" />
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
import Button from 'primevue/button'
import ThemeToggle from './components/ThemeToggle.vue'
import AccountMenu from './components/AccountMenu.vue'

const route = useRoute()

const showAuthActions = computed(() => route.meta.requiresAuth !== true)
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

.navbar-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.nav-link-wrap {
  text-decoration: none;
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

  .navbar-actions {
    gap: 0.5rem;
  }

  .navbar-right {
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  .navbar-title {
    font-size: 1.25rem;
  }

  .app-main {
    padding: 1rem;
  }
}
</style>
