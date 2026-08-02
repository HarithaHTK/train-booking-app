import { ref, onMounted } from 'vue'
import type { ThemeMode } from '../config/theme'
import { getThemeColors } from '../config/theme'

const currentTheme = ref<ThemeMode>('dark')
const isInitialized = ref(false)

const THEME_STORAGE_KEY = 'portal-theme-mode'

export function useTheme() {
  /**
   * Initialize theme from localStorage or system preference
   */
  const initTheme = () => {
    if (isInitialized.value) return

    const savedTheme = localStorage.getItem(THEME_STORAGE_KEY) as ThemeMode | null
    
    if (savedTheme && (savedTheme === 'dark' || savedTheme === 'light')) {
      currentTheme.value = savedTheme
    } else {
      // Detect system preference
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      currentTheme.value = prefersDark ? 'dark' : 'light'
    }

    applyTheme(currentTheme.value)
    isInitialized.value = true
  }

  /**
   * Apply theme to the DOM
   */
  const applyTheme = (mode: ThemeMode) => {
    const html = document.documentElement
    const colors = getThemeColors(mode)

    // Remove old theme class
    html.classList.remove('dark-mode', 'light-mode')
    
    // Add new theme class
    html.classList.add(`${mode}-mode`)

    // Apply CSS variables
    Object.entries(colors).forEach(([key, value]) => {
      html.style.setProperty(`--${key}`, value)
    })

    // Update data attribute for other frameworks
    html.setAttribute('data-theme', mode)
  }

  /**
   * Toggle between dark and light theme
   */
  const toggleTheme = () => {
    const newTheme = currentTheme.value === 'dark' ? 'light' : 'dark'
    setTheme(newTheme)
  }

  /**
   * Set specific theme
   */
  const setTheme = (mode: ThemeMode) => {
    currentTheme.value = mode
    localStorage.setItem(THEME_STORAGE_KEY, mode)
    applyTheme(mode)
  }

  /**
   * Get current theme
   */
  const getTheme = () => currentTheme.value

  /**
   * Get colors for current theme
   */
  const getColors = () => getThemeColors(currentTheme.value)

  // Listen to system preference changes
  onMounted(() => {
    initTheme()

    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    const handleChange = (e: MediaQueryListEvent) => {
      if (!localStorage.getItem(THEME_STORAGE_KEY)) {
        setTheme(e.matches ? 'dark' : 'light')
      }
    }

    mediaQuery.addEventListener('change', handleChange)
    
    return () => {
      mediaQuery.removeEventListener('change', handleChange)
    }
  })

  return {
    currentTheme,
    toggleTheme,
    setTheme,
    getTheme,
    getColors,
    initTheme,
  }
}
