import { adminRequest } from './http'

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
  seat_number?: string
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
  train?: ReservationTrain | null
}

export type Reservation = {
  id: number
  user_id: number
  schedule_id: number
  start_station_id: number
  leave_station_id: number
  seat_id: number
  status: ReservationStatus
  checked_in_at?: string | null
  checked_out_at?: string | null
  created_at?: string
  updated_at?: string
  deleted_at?: string | null
  user?: ReservationUser | null
  schedule?: ReservationSchedule | null
  startStation?: ReservationStation | null
  leaveStation?: ReservationStation | null
  seat?: ReservationSeat | null
}

export type ReservationPayload = {
  schedule_id: number
  start_station_id: number
  leave_station_id: number
  seat_id: number
  status: ReservationStatus
  checked_in_at?: string | null
  checked_out_at?: string | null
}

export type UpdateReservationPayload = Partial<ReservationPayload>

export async function fetchReservations(params: Record<string, string | number | boolean | undefined> = {}) {
  const query = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      query.set(key, String(value))
    }
  })

  const suffix = query.toString() ? `?${query.toString()}` : ''
  return adminRequest<{ reservations: Reservation[] }>(`/reservations${suffix}`)
}

export async function fetchReservation(reservationId: number) {
  return adminRequest<{ reservation: Reservation }>(`/reservations/${reservationId}`)
}

export async function updateReservation(reservationId: number, payload: UpdateReservationPayload) {
  return adminRequest<{ message: string; reservation: Reservation }>(`/reservations/${reservationId}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export async function deleteReservation(reservationId: number) {
  return adminRequest<{ message: string }>(`/reservations/${reservationId}`, {
    method: 'DELETE',
  })
}