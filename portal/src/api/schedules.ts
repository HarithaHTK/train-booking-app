import type { Station } from './stations'

export type ScheduleCoach = {
  id: number
  name?: string
  type?: string | null
  seat_count?: number | null
  total_seats?: number | null
  seats?: Array<{
    id: number
    seat_number?: number | string | null
    seat_class?: string | null
    is_reserved?: boolean | null
  }>
}

export type ScheduleTrain = {
  id: number
  train_number: string
  train_name: string
  coaches?: ScheduleCoach[]
}

export type ScheduleStation = {
  id: number
  station_id: number
  sequence: number
  arrival_time?: string | null
  departure_time?: string | null
  station?: Station
}

export type ScheduleRouteStation = {
  id: number
  route_id: number
  station_id: number
  sequence: number
  station?: Station
}

export type ScheduleRoute = {
  id: number
  name: string
  description?: string
  is_active: boolean
  stations?: ScheduleRouteStation[]
}

export type Schedule = {
  id: number
  train_id: number
  route_id: number
  departure_time?: string | null
  is_active: boolean
  train?: ScheduleTrain
  route?: ScheduleRoute | null
  station_schedules?: ScheduleStation[]
  created_by?: number | null
  updated_by?: number | null
  deleted_by?: number | null
  deleted_at?: string | null
  created_at?: string | null
  updated_at?: string | null
}

export type ReservationPayload = {
  schedule_id: number
  start_station_id: number
  leave_station_id: number
  seat_id?: number
  seat_ids?: number[]
  travel_date?: string | null
  status?: 'pending' | 'confirmed' | 'cancelled' | 'completed'
}

export type ReservationResponse = {
  message: string
  reservation: unknown
  reservations?: unknown[]
}

type ScheduleResponse = {
  schedule: Schedule
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

export async function fetchSchedule(scheduleId: number): Promise<ScheduleResponse> {
  return request(`/schedules/${scheduleId}`)
}

export async function createReservation(payload: ReservationPayload): Promise<ReservationResponse> {
  return request('/reservations', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}
