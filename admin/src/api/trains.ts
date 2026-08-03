import { adminRequest } from './http'
import type { Route } from './routes'

export type Train = {
  id: number
  train_number: string
  train_name: string
  route?: Route
  plan?: string
  is_active: boolean
  engines?: any[]
  coaches?: Array<{ id: number; name: string; seat_count?: number; type?: string }>
  created_at?: string
  updated_at?: string
}

export type TrainPayload = {
  train_number: string
  train_name: string
  is_active?: boolean
  engine_ids?: number[]
  coach_ids?: number[]
}

export async function fetchTrains(): Promise<{ trains: Train[] }> {
  return adminRequest<{ trains: Train[] }>('/trains')
}

export async function fetchTrain(id: number): Promise<{ train: Train }> {
  return adminRequest<{ train: Train }>(`/trains/${id}`)
}

export async function createTrain(payload: TrainPayload): Promise<{ message: string; train: Train }> {
  return adminRequest<{ message: string; train: Train }>('/trains', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function updateTrain(id: number, payload: Partial<TrainPayload>) {
  return adminRequest<{ message: string; train: Train }>(`/trains/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export async function deleteTrain(id: number) {
  return adminRequest<{ message: string }>(`/trains/${id}`, {
    method: 'DELETE',
  })
}

export default {
  fetchTrains,
  fetchTrain,
  createTrain,
  updateTrain,
  deleteTrain,
}
