<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrainRoute\StoreTrainRouteRequest;
use App\Http\Requests\TrainRoute\UpdateTrainRouteRequest;
use App\Models\RouteStation;
use App\Models\TrainRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class TrainRouteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/routes",
     *     tags={"Routes"},
     *     summary="List routes",
     *     operationId="listRoutes",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="order", in="query", required=false, description="Station order direction", @OA\Schema(type="string", enum={"asc", "desc"})),
     *     @OA\Response(response=200, description="Routes retrieved successfully", @OA\JsonContent(
     *         @OA\Property(property="routes", type="array", @OA\Items(ref="#/components/schemas/TrainRoute"))
     *     ))
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $order = $this->normalizeOrder($request->query('order', 'asc'));

        $routes = TrainRoute::query()
            ->latest()
            ->with([
                'routeStations' => fn ($query) => $query->with('station')->orderBy('sequence', $order),
            ])
            ->get()
            ->map(fn (TrainRoute $route) => $this->formatRoute($route))
            ->values();

        return response()->json([
            'routes' => $routes,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/routes",
     *     tags={"Routes"},
     *     summary="Create a route",
     *     operationId="createRoute",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name"},
     *         @OA\Property(property="name", type="string", example="Main Line"),
     *         @OA\Property(property="description", type="string", example="Primary railway route line"),
     *         @OA\Property(property="is_active", type="boolean", example=true)
     *     )),
     *     @OA\Response(response=201, description="Route created successfully", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Route created successfully."),
     *         @OA\Property(property="route", ref="#/components/schemas/TrainRoute")
     *     )),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function store(StoreTrainRouteRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()?->id;

        $route = TrainRoute::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $route->load(['routeStations' => fn ($query) => $query->with('station')->orderBy('sequence')]);

        return response()->json([
            'message' => 'Route created successfully.',
            'route' => $this->formatRoute($route),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/routes/{route}",
     *     tags={"Routes"},
     *     summary="Get a route",
     *     operationId="getRoute",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="route", in="path", required=true, description="Route ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="order", in="query", required=false, description="Station order direction", @OA\Schema(type="string", enum={"asc", "desc"})),
     *     @OA\Response(response=200, description="Route retrieved successfully", @OA\JsonContent(
     *         @OA\Property(property="route", ref="#/components/schemas/TrainRoute")
     *     ))
     * )
     */
    public function show(Request $request, TrainRoute $route): JsonResponse
    {
        $order = $this->normalizeOrder($request->query('order', 'asc'));

        $route->load([
            'routeStations' => fn ($query) => $query->with('station')->orderBy('sequence', $order),
        ]);

        return response()->json([
            'route' => $this->formatRoute($route),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/routes/{route}",
     *     tags={"Routes"},
     *     summary="Update a route",
     *     operationId="updateRoute",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="route", in="path", required=true, description="Route ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string", example="Main Line"),
     *         @OA\Property(property="description", type="string", example="Updated route details"),
     *         @OA\Property(property="is_active", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=200, description="Route updated successfully", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Route updated successfully."),
     *         @OA\Property(property="route", ref="#/components/schemas/TrainRoute")
     *     )),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function update(UpdateTrainRouteRequest $request, TrainRoute $route): JsonResponse
    {
        $validated = $request->validated();

        $route->fill($validated);
        $route->updated_by = $request->user()?->id;
        $route->save();

        $route->load(['routeStations' => fn ($query) => $query->with('station')->orderBy('sequence')]);

        return response()->json([
            'message' => 'Route updated successfully.',
            'route' => $this->formatRoute($route),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/routes/{route}",
     *     tags={"Routes"},
     *     summary="Delete a route",
     *     operationId="deleteRoute",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="route", in="path", required=true, description="Route ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Route deleted successfully", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Route deleted successfully.")
     *     ))
     * )
     */
    public function destroy(Request $request, TrainRoute $route): JsonResponse
    {
        $userId = $request->user()?->id;

        foreach ($route->routeStations()->get() as $routeStation) {
            $routeStation->deleted_by = $userId;
            $routeStation->save();
            $routeStation->delete();
        }

        $route->deleted_by = $userId;
        $route->save();
        $route->delete();

        return response()->json([
            'message' => 'Route deleted successfully.',
        ]);
    }

    private function formatRoute(TrainRoute $route): array
    {
        return [
            'id' => $route->id,
            'name' => $route->name,
            'description' => $route->description,
            'is_active' => $route->is_active,
            'created_by' => $route->created_by,
            'updated_by' => $route->updated_by,
            'deleted_by' => $route->deleted_by,
            'deleted_at' => $route->deleted_at,
            'created_at' => $route->created_at,
            'updated_at' => $route->updated_at,
            'stations' => $route->routeStations->map(fn (RouteStation $routeStation) => $this->formatRouteStation($routeStation))->values()->all(),
        ];
    }

    private function formatRouteStation(RouteStation $routeStation): array
    {
        return [
            'id' => $routeStation->id,
            'route_id' => $routeStation->route_id,
            'station_id' => $routeStation->station_id,
            'sequence' => $routeStation->sequence,
            'station' => $routeStation->station?->toArray(),
            'created_by' => $routeStation->created_by,
            'updated_by' => $routeStation->updated_by,
            'deleted_by' => $routeStation->deleted_by,
            'deleted_at' => $routeStation->deleted_at,
            'created_at' => $routeStation->created_at,
            'updated_at' => $routeStation->updated_at,
        ];
    }

    private function normalizeOrder(mixed $order): string
    {
        $order = strtolower((string) $order);

        return in_array($order, ['asc', 'desc'], true) ? $order : 'asc';
    }
}