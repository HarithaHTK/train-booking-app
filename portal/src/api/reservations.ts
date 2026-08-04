export type ReservationStatus = 'pending' | 'confirmed' | 'cancelled' | 'completed'

export type ReservationUser = {
  id: number
  name?: string
  email?: string
}

export type ReservationTrain = {
  id: number
  train_number?: string
  train_name?: string
}

export type ReservationStation = {
  id: number
  name?: string
}

export type ReservationSeat = {
  id: number
  seat_number?: string | number
  coach_id?: number
  coach?: {
    id: number
    name?: string
    type?: string
  } | null
}

export type ReservationSchedule = {
  id: number
  departure_time?: string | null
  route?: {
    id: number
    name?: string
  } | null
  train?: ReservationTrain | null
}

export type Reservation = {
  id: number
  user_id: number
  schedule_id: number
  start_station_id: number
  leave_station_id: number
  seat_id: number
  travel_date?: string | null
  status: ReservationStatus
  checked_in_at?: string | null
  checked_out_at?: string | null
  created_at?: string | null
  updated_at?: string | null
  deleted_at?: string | null
  user?: ReservationUser | null
  schedule?: ReservationSchedule | null
  start_station?: ReservationStation | null
  leave_station?: ReservationStation | null
  seat?: ReservationSeat | null
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

export async function fetchReservations(params: Record<string, string | number | boolean | undefined> = {}) {
  const query = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      query.set(key, String(value))
    }
  })

  const suffix = query.toString() ? `?${query.toString()}` : ''
  return request(`/reservations${suffix}`) as Promise<{ reservations: Reservation[] }>
}

export async function updateReservation(reservationId: number, payload: Partial<Pick<Reservation, 'status' | 'checked_in_at' | 'checked_out_at'>>) {
  return request(`/reservations/${reservationId}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  }) as Promise<{ message: string; reservation: Reservation }>
}
