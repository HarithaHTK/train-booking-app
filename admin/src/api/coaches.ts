import { adminRequest } from './http'

export type Coach = {
  id: number
  name: string
  seat_count?: number
  type?: 'reserved' | 'unreserved' | string
}

export async function fetchCoaches(): Promise<{ coaches: Coach[] }> {
  return adminRequest<{ coaches: Coach[] }>('/coaches')
}

export default { fetchCoaches }
