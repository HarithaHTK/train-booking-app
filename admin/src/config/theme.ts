/**
 * Minimalist Black & White Theme Configuration
 * Supports both dark and light modes
 */

export type ThemeMode = 'dark' | 'light'

export const themeConfig = {
  dark: {
    'bg-primary': '#000000',
    'bg-secondary': '#0a0a0a',
    'bg-tertiary': '#171717',
    'text-primary': '#f5f5f5',
    'text-secondary': '#d4d4d4',
    'text-tertiary': '#a3a3a3',
    'panel-bg': 'rgba(10, 10, 10, 0.96)',
    'input-bg': '#111111',
    'button-bg': '#1a1a1a',
    'border-color': 'rgba(255, 255, 255, 0.12)',
    'border-light': 'rgba(255, 255, 255, 0.06)',
    'accent-primary': '#e5e5e5',
    'accent-hover': '#ffffff',
    'accent-light': 'rgba(255, 255, 255, 0.08)',
  },
  
  light: {
    'bg-primary': '#ffffff',
    'bg-secondary': '#f5f5f5',
    'bg-tertiary': '#e5e5e5',
    'text-primary': '#111111',
    'text-secondary': '#404040',
    'text-tertiary': '#737373',
    'panel-bg': '#ffffff',
    'input-bg': '#fafafa',
    'button-bg': '#f4f4f5',
    'border-color': 'rgba(0, 0, 0, 0.12)',
    'border-light': 'rgba(0, 0, 0, 0.06)',
    'accent-primary': '#111111',
    'accent-hover': '#000000',
    'accent-light': 'rgba(17, 17, 17, 0.06)',
  },
}

export const getThemeColors = (mode: ThemeMode) => themeConfig[mode]
