import { adminRequest } from './http'

export type RouteStationItem = {
  id: number
  route_id: number
  station_id: number
  sequence: number
  station?: any
}

export type Route = {
  id: number
  name: string
  description?: string
  is_active: boolean
  stations?: RouteStationItem[]
  created_at?: string
  updated_at?: string
}

export type RoutePayload = {
  name: string
  description?: string
  is_active: boolean
}

export type UpdateRoutePayload = Partial<RoutePayload>

export async function fetchRoutes(): Promise<{ routes: Route[] }> {
  return adminRequest<{ routes: Route[] }>('/routes')
}

export async function fetchRoute(routeId: number): Promise<{ route: Route }> {
  return adminRequest<{ route: Route }>(`/routes/${routeId}`)
}

export async function createRoute(payload: RoutePayload): Promise<{ message: string; route: Route }> {
  return adminRequest<{ message: string; route: Route }>('/routes', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function updateRoute(routeId: number, payload: UpdateRoutePayload): Promise<{ message: string; route: Route }> {
  return adminRequest<{ message: string; route: Route }>(`/routes/${routeId}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export async function deleteRoute(routeId: number): Promise<{ message: string }> {
  return adminRequest<{ message: string }>(`/routes/${routeId}`, {
    method: 'DELETE',
  })
}
