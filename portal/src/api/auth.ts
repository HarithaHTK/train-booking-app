export type AuthUser = {
  id: number
  name: string
  email: string
  created_at?: string
  updated_at?: string
}

type AuthResponse = {
  message: string
  user: AuthUser
  token: string
}

async function request(path: string, init: RequestInit = {}): Promise<any> {
  const token = localStorage.getItem('auth_token')
  const headers = new Headers(init.headers || {})

  headers.set('Accept', 'application/json')
  if (!headers.has('Content-Type') && !(init.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json')
  }

  if (token) {
    headers.set('Authorization', `Bearer ${token}`)
  }

  const response = await fetch(`/api${path}`, {
    ...init,
    headers,
  })

  const data = await response.json().catch(() => ({}))

  if (!response.ok) {
    throw new Error(data.message || 'Request failed')
  }

  return data
}

export async function registerUser(payload: { name: string; email: string; password: string; password_confirmation: string }): Promise<AuthResponse> {
  return request('/register', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function loginUser(payload: { email: string; password: string }): Promise<AuthResponse> {
  return request('/login', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function logoutUser(): Promise<{ message: string }> {
  return request('/logout', {
    method: 'POST',
  })
}

export async function fetchCurrentUser(): Promise<{ user: AuthUser }> {
  return request('/me')
}

export async function fetchDashboard(): Promise<{ message: string; user: AuthUser }> {
  return request('/dashboard')
}
