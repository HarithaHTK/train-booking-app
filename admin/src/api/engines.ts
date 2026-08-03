import { adminRequest } from './http'

export type Engine = {
  id: number
  name: string
  serial?: string
}

export async function fetchEngines(): Promise<{ engines: Engine[] }> {
  return adminRequest<{ engines: Engine[] }>('/engines')
}

export default { fetchEngines }
