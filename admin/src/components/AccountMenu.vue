<template>
  <div class="account-menu-wrapper">
    <button 
      class="account-btn"
      @click="toggleMenu"
      type="button"
    >
      <i class="pi pi-user"></i>
    </button>

    <div v-show="isOpen" class="dropdown-panel">
      <div class="user-section">
        <div class="user-info">
          <p class="user-name">{{ user?.name || 'User' }}</p>
          <p class="user-email">{{ user?.email || 'Email' }}</p>
        </div>
      </div>

      <button @click="toggleTheme" class="dropdown-btn">
        <i :class="currentTheme === 'dark' ? 'pi pi-sun' : 'pi pi-moon'"></i>
        <span>{{ currentTheme === 'dark' ? 'Light Mode' : 'Dark Mode' }}</span>
      </button>

      <button @click="logout" class="dropdown-btn logout-btn">
        <i class="pi pi-sign-out"></i>
        <span>Logout</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { fetchCurrentUser, logoutUser, type AuthUser } from '../api/auth'
import { useTheme } from '../composables/useTheme'

const router = useRouter()
const { currentTheme, toggleTheme } = useTheme()

const user = ref<AuthUser | null>(null)
const isOpen = ref(false)

onMounted(async () => {
  try {
    const response = await fetchCurrentUser()
    user.value = response.user
  } catch (err) {
    console.error('Failed to load user:', err)
  }
})

function toggleMenu() {
  isOpen.value = !isOpen.value
}

async function logout() {
  try {
    await logoutUser()
  } catch (err) {
    console.error('Logout error:', err)
  } finally {
    localStorage.removeItem('admin_auth_token')
      localStorage.removeItem('admin_auth_user')
    router.push({ name: 'auth-login' })
  }
}

// Close dropdown when clicking outside
function handleClickOutside(e: MouseEvent) {
  const menu = document.querySelector('.account-menu-wrapper')
  if (menu && !menu.contains(e.target as Node)) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.account-menu-wrapper {
  position: relative;
  display: inline-block;
}

.account-btn {
  background: transparent;
  border: 1px solid var(--border-color);
  color: var(--text-primary);
  border-radius: 50%;
  width: 40px;
  height: 40px;
  padding: 0;
  font-size: 1.5rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.account-btn:hover {
  background: var(--bg-tertiary);
  border-color: var(--accent-primary);
  color: var(--accent-primary);
}

.account-btn:focus {
  outline: none;
}

.dropdown-panel {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background: var(--panel-bg);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 220px;
  z-index: 1000;
  animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.user-section {
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-light);
  background: var(--accent-light);
}

.user-info {
  margin: 0;
}

.user-name {
  margin: 0;
  font-weight: 600;
  font-size: 14px;
  color: var(--text-primary);
}

.user-email {
  margin: 4px 0 0 0;
  font-size: 12px;
  color: var(--text-secondary);
  word-break: break-all;
}

.dropdown-btn {
  width: 100%;
  padding: 10px 16px;
  background: transparent;
  border: none;
  color: var(--text-primary);
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 12px;
  text-align: left;
  transition: background 0.2s ease;
}

.dropdown-btn:hover {
  background: var(--bg-tertiary);
}

.dropdown-btn i {
  width: 18px;
  flex-shrink: 0;
}

.logout-btn {
  color: var(--accent-primary);
  border-top: 1px solid var(--border-light);
}

.logout-btn:hover {
  background: rgba(220, 38, 38, 0.08);
}
</style>
