<?php

namespace App\Http\Controllers;

use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Requests\Schedule\UpdateScheduleRequest;
use App\Http\Requests\Schedule\UpdateScheduleStationRequest;
use App\Models\RouteStation;
use App\Models\Schedule;
use App\Models\ScheduleStation;
use App\Models\Station;
use App\Models\TrainRoute;
use Illuminate\Http\Request as BaseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use OpenApi\Annotations as OA;

class ScheduleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/schedules",
     *     tags={"Schedules"},
     *     summary="List schedules",
     *     operationId="listSchedules",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Schedules retrieved successfully", @OA\JsonContent(
     *         @OA\Property(property="schedules", type="array", @OA\Items(ref="#/components/schemas/Schedule"))
     *     ))
     * )
     */
    public function index(): JsonResponse
    {
        $schedules = Schedule::query()
            ->latest()
            ->with([
                'train.coaches.seats',
                'route.routeStations' => fn ($query) => $query->with('station')->orderBy('sequence', 'asc'),
                'stationSchedules' => fn ($query) => $query->with('station')->orderBy('sequence', 'asc'),
            ])
            ->get()
            ->map(fn (Schedule $schedule) => $this->formatSchedule($schedule))
            ->values();

        return response()->json([
            'schedules' => $schedules,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/route-search/by-station/{station}",
     *     tags={"Schedules"},
     *     summary="Find routes and schedules by station direction",
     *     operationId="searchRoutesByStation",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="station", in="path", required=true, description="Station ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Matching routes and schedules retrieved successfully"),
     *     @OA\Response(response=404, description="Station not found")
     * )
     */
    public function searchRoutesByStation(Station $station): JsonResponse
    {
        $routes = TrainRoute::query()
            ->where('is_active', true)
            ->with([
                'routeStations' => fn ($query) => $query->with('station')->orderBy('sequence', 'asc'),
                'schedules' => fn ($query) => $query->with([
                    'train',
                    'stationSchedules' => fn ($stationQuery) => $stationQuery->with('station')->orderBy('sequence', 'asc'),
                ])->orderBy('created_at', 'desc'),
            ])
            ->get()
            ->filter(function ($route) use ($station) {
                $matchedIndex = $route->routeStations->search(fn (RouteStation $routeStation) => $routeStation->station_id === $station->id);

                return $matchedIndex !== false && $matchedIndex < $route->routeStations->count() - 1;
            })
            ->map(function ($route) use ($station) {
                $routeStations = $route->routeStations->values();
                $matchedIndex = $routeStations->search(fn (RouteStation $routeStation) => $routeStation->station_id === $station->id);

                return [
                    'id' => $route->id,
                    'name' => $route->name,
                    'description' => $route->description,
                    'is_active' => $route->is_active,
                    'stations' => $routeStations->map(fn (RouteStation $routeStation) => [
                        'id' => $routeStation->id,
                        'route_id' => $routeStation->route_id,
                        'station_id' => $routeStation->station_id,
                        'sequence' => $routeStation->sequence,
                        'station' => $routeStation->station?->toArray(),
                    ])->values()->all(),
                    'matched_station_id' => $station->id,
                    'matched_sequence' => $routeStations[$matchedIndex]->sequence ?? null,
                    'forward_stations' => $routeStations->slice($matchedIndex + 1)->map(fn (RouteStation $routeStation) => [
                        'id' => $routeStation->id,
                        'route_id' => $routeStation->route_id,
                        'station_id' => $routeStation->station_id,
                        'sequence' => $routeStation->sequence,
                        'station' => $routeStation->station?->toArray(),
                    ])->values()->all(),
                ];
            })
            ->values();

        $matchedRouteIds = $routes->pluck('id')->all();

        $schedules = Schedule::query()
            ->whereIn('route_id', $matchedRouteIds)
            ->with([
                'train.coaches.seats',
                'route.routeStations' => fn ($query) => $query->with('station')->orderBy('sequence', 'asc'),
                'stationSchedules' => fn ($query) => $query->with('station')->orderBy('sequence', 'asc'),
            ])
            ->latest()
            ->get()
            ->map(fn (Schedule $schedule) => $this->formatSchedule($schedule))
            ->values();

        return response()->json([
            'station' => $station,
            'routes' => $routes,
            'schedules' => $schedules,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/schedules",
     *     tags={"Schedules"},
     *     summary="Create a schedule",
     *     operationId="createSchedule",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="train_id", type="integer", example=1),
     *         @OA\Property(property="route_id", type="integer", example=1),
     *         @OA\Property(property="departure_time", type="string", format="time", example="20:00:00"),
     *         @OA\Property(property="is_active", type="boolean", example=true)
     *     )),
     *     @OA\Response(response=201, description="Schedule created successfully", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Schedule created successfully."),
     *         @OA\Property(property="schedule", ref="#/components/schemas/Schedule")
     *     )),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()?->id;

        $schedule = Schedule::create([
            'train_id' => $validated['train_id'],
            'route_id' => $validated['route_id'],
            'departure_time' => $validated['departure_time'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $schedule->load([
            'train.coaches.seats',
            'route.routeStations' => fn ($query) => $query->with('station')->orderBy('sequence', 'asc'),
            'stationSchedules' => fn ($query) => $query->with('station')->orderBy('sequence', 'asc'),
        ]);

        return response()->json([
            'message' => 'Schedule created successfully.',
            'schedule' => $this->formatSchedule($schedule),
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/schedules/{schedule}",
     *     tags={"Schedules"},
     *     summary="Get a schedule",
     *     operationId="getSchedule",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="schedule", in="path", required=true, description="Schedule ID", @OA\Schema(type="integer")),
    *     @OA\Response(response=200, description="Schedule retrieved successfully", @OA\JsonContent(
    *         @OA\Property(property="schedule", ref="#/components/schemas/Schedule")
    *     )),
    *     @OA\Response(response=404, description="Schedule not found")
     * )
     */
    public function show(Request $request, Schedule $schedule): JsonResponse
    {
        $schedule->load([
            'train.coaches.seats',
            'route.routeStations' => fn ($query) => $query->with('station')->orderBy('sequence', 'asc'),
            'stationSchedules' => fn ($query) => $query->with('station')->orderBy('sequence', 'asc'),
        ]);

        return response()->json([
            'schedule' => $this->formatSchedule($schedule),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/schedules/{schedule}",
     *     tags={"Schedules"},
     *     summary="Update a schedule",
     *     operationId="updateSchedule",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="schedule", in="path", required=true, description="Schedule ID", @OA\Schema(type="integer")),
        *     @OA\RequestBody(required=true, @OA\JsonContent(
        *         @OA\Property(property="train_id", type="integer", example=1),
        *         @OA\Property(property="route_id", type="integer", example=1),
        *         @OA\Property(property="departure_time", type="string", format="time", example="20:00:00"),
        *         @OA\Property(property="is_active", type="boolean", example=true)
        *     )),
        *     @OA\Response(response=200, description="Schedule updated successfully", @OA\JsonContent(
        *         @OA\Property(property="message", type="string", example="Schedule updated successfully."),
        *         @OA\Property(property="schedule", ref="#/components/schemas/Schedule")
        *     )),
        *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function update(UpdateScheduleRequest $request, Schedule $schedule): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()?->id;

        $schedule->fill($validated);
        $schedule->updated_by = $userId;
        $schedule->save();

        $schedule->load([
            'stationSchedules' => fn ($query) => $query->with('station')->orderBy('sequence', 'asc'),
        ]);

        return response()->json([
            'message' => 'Schedule updated successfully.',
            'schedule' => $this->formatSchedule($schedule),
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/schedules/{schedule}/stations/{schedule_station}",
     *     tags={"Schedules"},
     *     summary="Update a schedule station",
     *     operationId="updateScheduleStationById",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="schedule", in="path", required=true, description="Schedule ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="schedule_station", in="path", required=true, description="Schedule station ID", @OA\Schema(type="integer")),
        *     @OA\RequestBody(required=true, @OA\JsonContent(
        *         @OA\Property(property="schedule_id", type="integer", example=1),
        *         @OA\Property(property="station_id", type="integer", example=2),
        *         @OA\Property(property="sequence", type="integer", example=3),
        *         @OA\Property(property="arrival_time", type="string", format="time", example="20:30:00"),
        *         @OA\Property(property="departure_time", type="string", format="time", example="20:35:00")
        *     )),
        *     @OA\Response(response=200, description="Schedule station updated successfully", @OA\JsonContent(
        *         @OA\Property(property="message", type="string", example="Schedule station updated successfully."),
        *         @OA\Property(property="schedule_station", ref="#/components/schemas/ScheduleStation")
        *     )),
        *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function updateStation(UpdateScheduleStationRequest $request, Schedule $schedule, ScheduleStation $scheduleStation): JsonResponse
    {
        return $this->updateScheduleStation($request, $schedule, $scheduleStation);
    }

    /**
     * @OA\Patch(
     *     path="/api/schedules/{schedule}/stations/by-station/{station}",
     *     tags={"Schedules"},
     *     summary="Update a schedule station by station ID",
     *     operationId="updateScheduleStationByStation",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="schedule", in="path", required=true, description="Schedule ID", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="station", in="path", required=true, description="Station ID", @OA\Schema(type="integer")),
        *     @OA\RequestBody(required=true, @OA\JsonContent(
        *         @OA\Property(property="schedule_id", type="integer", example=1),
        *         @OA\Property(property="station_id", type="integer", example=2),
        *         @OA\Property(property="sequence", type="integer", example=3),
        *         @OA\Property(property="arrival_time", type="string", format="time", example="20:30:00"),
        *         @OA\Property(property="departure_time", type="string", format="time", example="20:35:00")
        *     )),
        *     @OA\Response(response=200, description="Schedule station updated successfully", @OA\JsonContent(
        *         @OA\Property(property="message", type="string", example="Schedule station updated successfully."),
        *         @OA\Property(property="schedule_station", ref="#/components/schemas/ScheduleStation")
        *     )),
        *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function updateStationByStation(UpdateScheduleStationRequest $request, Schedule $schedule, int $station): JsonResponse
    {
        $scheduleStation = $schedule->stationSchedules()->withTrashed()->where('station_id', $station)->first();

        if (! $scheduleStation) {
            return $this->storeScheduleStation($request, $schedule, $station);
        }

        if ($scheduleStation->trashed()) {
            $scheduleStation->restore();
        }

        return $this->updateScheduleStation($request, $schedule, $scheduleStation);
    }

    private function storeScheduleStation(BaseRequest $request, Schedule $schedule, int $station): JsonResponse
    {
        $validated = $request->validated();
        $userId = $request->user()?->id;

        $scheduleStation = $schedule->stationSchedules()->create([
            'schedule_id' => $schedule->id,
            'station_id' => $station,
            'sequence' => $validated['sequence'],
            'arrival_time' => $validated['arrival_time'] ?? null,
            'departure_time' => $validated['departure_time'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $scheduleStation->load('station');

        return response()->json([
            'message' => 'Schedule station created successfully.',
            'schedule_station' => $this->formatScheduleStation($scheduleStation),
        ], 201);
    }

    private function updateScheduleStation(UpdateScheduleStationRequest $request, Schedule $schedule, ScheduleStation $scheduleStation): JsonResponse
    {
        abort_unless($scheduleStation->schedule_id === $schedule->id, 404);

        $validated = $request->validated();
        $userId = $request->user()?->id;

        $scheduleStation->fill($validated);
        $scheduleStation->updated_by = $userId;
        $scheduleStation->save();
        $scheduleStation->load('station');

        return response()->json([
            'message' => 'Schedule station updated successfully.',
            'schedule_station' => $this->formatScheduleStation($scheduleStation),
        ]);
    }

    private function formatSchedule(Schedule $schedule): array
    {
        $departureTime = $schedule->departure_time instanceof Carbon
            ? $schedule->departure_time->format('H:i:s')
            : $schedule->departure_time;

        return [
            'id' => $schedule->id,
            'train_id' => $schedule->train_id,
            'route_id' => $schedule->route_id,
            'departure_time' => $departureTime,
            'is_active' => $schedule->is_active,
            'train' => $schedule->train?->toArray(),
            'route' => $schedule->route ? [
                ...$schedule->route->toArray(),
                'stations' => $schedule->route->routeStations->map(fn ($routeStation) => [
                    'id' => $routeStation->id,
                    'route_id' => $routeStation->route_id,
                    'station_id' => $routeStation->station_id,
                    'sequence' => $routeStation->sequence,
                    'station' => $routeStation->station?->toArray(),
                ])->values()->all(),
            ] : null,
            'created_by' => $schedule->created_by,
            'updated_by' => $schedule->updated_by,
            'deleted_by' => $schedule->deleted_by,
            'deleted_at' => $schedule->deleted_at,
            'created_at' => $schedule->created_at,
            'updated_at' => $schedule->updated_at,
            'station_schedules' => $schedule->stationSchedules->map(fn (ScheduleStation $scheduleStation) => $this->formatScheduleStation($scheduleStation))->values()->all(),
        ];
    }

    private function formatScheduleStation(ScheduleStation $scheduleStation): array
    {
        $arrivalTime = $scheduleStation->arrival_time instanceof Carbon
            ? $scheduleStation->arrival_time->format('H:i:s')
            : $scheduleStation->arrival_time;

        $departureTime = $scheduleStation->departure_time instanceof Carbon
            ? $scheduleStation->departure_time->format('H:i:s')
            : $scheduleStation->departure_time;

        return [
            'id' => $scheduleStation->id,
            'schedule_id' => $scheduleStation->schedule_id,
            'station_id' => $scheduleStation->station_id,
            'sequence' => $scheduleStation->sequence,
            'arrival_time' => $arrivalTime,
            'departure_time' => $departureTime,
            'station' => $scheduleStation->station?->toArray(),
            'created_by' => $scheduleStation->created_by,
            'updated_by' => $scheduleStation->updated_by,
            'deleted_by' => $scheduleStation->deleted_by,
            'deleted_at' => $scheduleStation->deleted_at,
            'created_at' => $scheduleStation->created_at,
            'updated_at' => $scheduleStation->updated_at,
        ];
    }
}
