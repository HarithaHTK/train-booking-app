import { adminRequest } from './http'

export type Station = {
  id: number
  name: string
  address: string
  is_active: boolean
  created_at?: string
  updated_at?: string
}

export type StationPayload = {
  name: string
  address: string
  is_active: boolean
}

export type UpdateStationPayload = Partial<StationPayload>

export async function fetchStations(): Promise<{ stations: Station[] }> {
  return adminRequest<{ stations: Station[] }>('/stations')
}

export async function createStation(payload: StationPayload): Promise<{ message: string; station: Station }> {
  return adminRequest<{ message: string; station: Station }>('/stations', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function updateStation(
  stationId: number,
  payload: UpdateStationPayload,
): Promise<{ message: string; station: Station }> {
  return adminRequest<{ message: string; station: Station }>(`/stations/${stationId}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export async function deleteStation(stationId: number): Promise<{ message: string }> {
  return adminRequest<{ message: string }>(`/stations/${stationId}`, {
    method: 'DELETE',
  })
}
