<?php

namespace App\Http\Controllers;

use App\Http\Requests\RouteStation\StoreRouteStationRequest;
use App\Http\Requests\RouteStation\UpdateRouteStationRequest;
use App\Models\RouteStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class RouteStationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/route-stations",
     *     tags={"Route Stations"},
     *     summary="List route stations",
     *     operationId="listRouteStations",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Route stations retrieved successfully", @OA\JsonContent(
     *         @OA\Property(property="route_stations", type="array", @OA\Items(ref="#/components/schemas/RouteStation"))
     *     ))
     * )
     */
    public function index(): JsonResponse
    {
        $routeStations = RouteStation::query()
            ->with('station')
            ->orderBy('route_id')
            ->orderBy('sequence')
            ->get()
            ->map(fn (RouteStation $routeStation) => $this->formatRouteStation($routeStation))
            ->values();

        return response()->json([
            'route_stations' => $routeStations,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/route-stations",
     *     tags={"Route Stations"},
     *     summary="Create a route station",
     *     operationId="createRouteStation",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"route_id", "station_id", "sequence"},
     *         @OA\Property(property="route_id", type="integer", example=1),
     *         @OA\Property(property="station_id", type="integer", example=2),
     *         @OA\Property(property="sequence", type="integer", example=1)
     *     )),
     *     @OA\Response(response=201, description="Route station created successfully", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Route station created successfully."),
     *         @OA\Property(property="route_station", ref="#/components/schemas/RouteStation")
     *     )),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function store(StoreRouteStationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()?->id;

        $routeStation = RouteStation::create([
            'route_id' => $validated['route_id'],
            'station_id' => $validated['station_id'],
            'sequence' => $validated['sequence'],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $routeStation->load('station');

        return response()->json([
            'message' => 'Route station created successfully.',
            'route_station' => $this->formatRouteStation($routeStation),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/route-stations/{route_station}",
     *     tags={"Route Stations"},
     *     summary="Get a route station",
     *     operationId="getRouteStation",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="route_station", in="path", required=true, description="Route station ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Route station retrieved successfully", @OA\JsonContent(
     *         @OA\Property(property="route_station", ref="#/components/schemas/RouteStation")
     *     ))
     * )
     */
    public function show(RouteStation $routeStation): JsonResponse
    {
        $routeStation->load('station');

        return response()->json([
            'route_station' => $this->formatRouteStation($routeStation),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/route-stations/{route_station}",
     *     tags={"Route Stations"},
     *     summary="Update a route station",
     *     operationId="updateRouteStation",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="route_station", in="path", required=true, description="Route station ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="route_id", type="integer", example=1),
     *         @OA\Property(property="station_id", type="integer", example=2),
     *         @OA\Property(property="sequence", type="integer", example=3)
     *     )),
     *     @OA\Response(response=200, description="Route station updated successfully", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Route station updated successfully."),
     *         @OA\Property(property="route_station", ref="#/components/schemas/RouteStation")
     *     )),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function update(UpdateRouteStationRequest $request, RouteStation $routeStation): JsonResponse
    {
        $validated = $request->validated();

        $routeStation->fill($validated);
        $routeStation->updated_by = $request->user()?->id;
        $routeStation->save();
        $routeStation->load('station');

        return response()->json([
            'message' => 'Route station updated successfully.',
            'route_station' => $this->formatRouteStation($routeStation),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/route-stations/{route_station}",
     *     tags={"Route Stations"},
     *     summary="Delete a route station",
     *     operationId="deleteRouteStation",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="route_station", in="path", required=true, description="Route station ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Route station deleted successfully", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Route station deleted successfully.")
     *     ))
     * )
     */
    public function destroy(Request $request, RouteStation $routeStation): JsonResponse
    {
        $routeStation->deleted_by = $request->user()?->id;
        $routeStation->save();
        $routeStation->delete();

        return response()->json([
            'message' => 'Route station deleted successfully.',
        ]);
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
}