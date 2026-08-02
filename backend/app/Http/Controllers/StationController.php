<?php

namespace App\Http\Controllers;

use App\Http\Requests\Station\StoreStationRequest;
use App\Http\Requests\Station\UpdateStationRequest;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class StationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/stations",
     *     tags={"Stations"},
     *     summary="List stations",
     *     operationId="listStations",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Stations retrieved successfully", @OA\JsonContent(
     *         @OA\Property(property="stations", type="array", @OA\Items(ref="#/components/schemas/Station"))
     *     ))
     * )
     */
    public function index(): JsonResponse
    {
        $stations = Station::query()
            ->latest()
            ->get();

        return response()->json([
            'stations' => $stations,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/stations",
     *     tags={"Stations"},
     *     summary="Create a station",
     *     operationId="createStation",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name", "address"},
     *         @OA\Property(property="name", type="string", example="Central Station"),
     *         @OA\Property(property="address", type="string", example="123 Main Street, City"),
     *         @OA\Property(property="is_active", type="boolean", example=true)
     *     )),
     *     @OA\Response(response=201, description="Station created successfully", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Station created successfully."),
     *         @OA\Property(property="station", ref="#/components/schemas/Station")
     *     )),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function store(StoreStationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()?->id;

        $station = Station::create([
            'name' => $validated['name'],
            'address' => $validated['address'],
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return response()->json([
            'message' => 'Station created successfully.',
            'station' => $station,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/stations/{station}",
     *     tags={"Stations"},
     *     summary="Get a station",
     *     operationId="getStation",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="station", in="path", required=true, description="Station ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Station retrieved successfully", @OA\JsonContent(
     *         @OA\Property(property="station", ref="#/components/schemas/Station")
     *     ))
     * )
     */
    public function show(Station $station): JsonResponse
    {
        return response()->json([
            'station' => $station,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/stations/{station}",
     *     tags={"Stations"},
     *     summary="Update a station",
     *     operationId="updateStation",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="station", in="path", required=true, description="Station ID", @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string", example="Central Station"),
     *         @OA\Property(property="address", type="string", example="123 Main Street, City"),
     *         @OA\Property(property="is_active", type="boolean", example=false)
     *     )),
     *     @OA\Response(response=200, description="Station updated successfully", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Station updated successfully."),
     *         @OA\Property(property="station", ref="#/components/schemas/Station")
     *     )),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function update(UpdateStationRequest $request, Station $station): JsonResponse
    {
        $validated = $request->validated();

        $station->fill($validated);
        $station->updated_by = $request->user()?->id;
        $station->save();

        return response()->json([
            'message' => 'Station updated successfully.',
            'station' => $station->fresh(),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/stations/{station}",
     *     tags={"Stations"},
     *     summary="Delete a station",
     *     operationId="deleteStation",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="station", in="path", required=true, description="Station ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Station deleted successfully", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Station deleted successfully.")
     *     ))
     * )
     */
    public function destroy(Request $request, Station $station): JsonResponse
    {
        $station->deleted_by = $request->user()?->id;
        $station->save();
        $station->delete();

        return response()->json([
            'message' => 'Station deleted successfully.',
        ]);
    }
}
