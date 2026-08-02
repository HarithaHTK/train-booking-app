import { adminRequest } from './http'

export type AuthUser = {
  id: number
  name: string
  email: string
  roles?: string[]
  created_at?: string
  updated_at?: string
}

type AuthResponse = {
  message: string
  user: AuthUser
  roles?: string[]
  token: string
}

export async function loginUser(payload: { email: string; password: string }): Promise<AuthResponse> {
  return adminRequest<AuthResponse>('/login', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function logoutUser(): Promise<{ message: string }> {
  return adminRequest<{ message: string }>('/logout', {
    method: 'POST',
  })
}

export async function fetchCurrentUser(): Promise<{ user: AuthUser; roles?: string[] }> {
  return adminRequest<{ user: AuthUser; roles?: string[] }>('/me')
}

export async function fetchDashboard(): Promise<{ message: string; user: AuthUser }> {
  return adminRequest<{ message: string; user: AuthUser }>('/dashboard')
}
