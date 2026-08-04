export type Station = {
  id: number
  name: string
  address: string
  is_active: boolean
  created_at?: string
  updated_at?: string
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
    if (response.status === 403) {
      throw new Error(data.message || 'Forbidden. Insufficient role.')
    }

    throw new Error(data.message || 'Request failed')
  }

  return data
}

export async function fetchStations(): Promise<{ stations: Station[] }> {
  return request('/stations')
}