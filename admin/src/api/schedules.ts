import { adminRequest } from './http'
import type { Route } from './routes'
import type { Train } from './trains'

export type ScheduleStationItem = {
  id: number
  schedule_id: number
  station_id: number
  sequence: number
  arrival_time?: string | null
  departure_time?: string | null
  station?: {
    id: number
    name: string
    address?: string
    is_active?: boolean
  }
  created_at?: string
  updated_at?: string
}

export type Schedule = {
  id: number
  train_id: number
  route_id: number
  departure_time?: string | null
  is_active: boolean
  created_by?: number | null
  updated_by?: number | null
  deleted_by?: number | null
  deleted_at?: string | null
  created_at?: string
  updated_at?: string
  station_schedules?: ScheduleStationItem[]
  train?: Train
  route?: Route
}

export type SchedulePayload = {
  train_id: number
  route_id: number
  departure_time?: string | null
  is_active: boolean
}

export type UpdateSchedulePayload = Partial<SchedulePayload>

export type ScheduleStationPayload = {
  schedule_id: number
  station_id: number
  sequence: number
  arrival_time?: string | null
  departure_time?: string | null
}

export type UpdateScheduleStationPayload = Partial<ScheduleStationPayload>

export async function fetchSchedules(): Promise<{ schedules: Schedule[] }> {
  return adminRequest<{ schedules: Schedule[] }>('/schedules')
}

export async function fetchSchedule(scheduleId: number): Promise<{ schedule: Schedule }> {
  return adminRequest<{ schedule: Schedule }>(`/schedules/${scheduleId}`)
}

export async function createSchedule(
  payload: SchedulePayload,
): Promise<{ message: string; schedule: Schedule }> {
  return adminRequest<{ message: string; schedule: Schedule }>('/schedules', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function updateSchedule(
  scheduleId: number,
  payload: UpdateSchedulePayload,
): Promise<{ message: string; schedule: Schedule }> {
  return adminRequest<{ message: string; schedule: Schedule }>(`/schedules/${scheduleId}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export async function updateScheduleStationById(
  scheduleId: number,
  scheduleStationId: number,
  payload: UpdateScheduleStationPayload,
): Promise<{ message: string; schedule_station: ScheduleStationItem }> {
  return adminRequest<{ message: string; schedule_station: ScheduleStationItem }>(
    `/schedules/${scheduleId}/stations/${scheduleStationId}`,
    {
      method: 'PATCH',
      body: JSON.stringify(payload),
    },
  )
}

export async function updateScheduleStationByStation(
  scheduleId: number,
  stationId: number,
  payload: UpdateScheduleStationPayload,
): Promise<{ message: string; schedule_station: ScheduleStationItem }> {
  return adminRequest<{ message: string; schedule_station: ScheduleStationItem }>(
    `/schedules/${scheduleId}/stations/by-station/${stationId}`,
    {
      method: 'PATCH',
      body: JSON.stringify(payload),
    },
  )
}

export default {
  fetchSchedules,
  fetchSchedule,
  createSchedule,
  updateSchedule,
  updateScheduleStationById,
  updateScheduleStationByStation,
}
