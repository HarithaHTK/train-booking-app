<?php

/**
 * @OA\Info(
 *     title="Train Booking API",
 *     version="1.0.0",
 *     description="OpenAPI documentation for the Train Booking backend"
 * )
 * @OA\Server(
 *     url="/api",
 *     description="Local API base path"
 * )
 */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CoachController;
use App\Http\Controllers\EngineController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\RouteStationController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\TrainCoachController;
use App\Http\Controllers\TrainController;
use App\Http\Controllers\TrainEngineController;
use App\Http\Controllers\TrainRouteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['allow.cors'])->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'message' => 'Laravel API is running',
            'timestamp' => now()->toISOString(),
            'service' => 'train-booking-api',
        ]);
    });

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/stations', [StationController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        // Dashboard is admin-only
        Route::get('/dashboard', [AuthController::class, 'dashboard'])
            ->middleware(\App\Http\Middleware\RequireRole::class . ':admin');

        Route::middleware(\App\Http\Middleware\RequireRole::class . ':admin')
            ->apiResource('routes', TrainRouteController::class);

        Route::middleware(\App\Http\Middleware\RequireRole::class . ':admin')
            ->apiResource('trains', TrainController::class);

        Route::middleware(\App\Http\Middleware\RequireRole::class . ':admin')
            ->apiResource('engines', EngineController::class);

        Route::middleware(\App\Http\Middleware\RequireRole::class . ':admin')
            ->apiResource('coaches', CoachController::class);

        Route::middleware(\App\Http\Middleware\RequireRole::class . ':admin')
            ->apiResource('seats', SeatController::class);

        Route::middleware(\App\Http\Middleware\RequireRole::class . ':admin')
            ->apiResource('train-engines', TrainEngineController::class);

        Route::middleware(\App\Http\Middleware\RequireRole::class . ':admin')
            ->apiResource('train-coaches', TrainCoachController::class);

        Route::middleware(\App\Http\Middleware\RequireRole::class . ':admin')
            ->apiResource('route-stations', RouteStationController::class);

        Route::middleware(\App\Http\Middleware\RequireRole::class . ':admin')->group(function () {
            Route::get('schedules', [ScheduleController::class, 'index']);
            Route::post('schedules', [ScheduleController::class, 'store']);
            Route::get('schedules/{schedule}', [ScheduleController::class, 'show']);
            Route::put('schedules/{schedule}', [ScheduleController::class, 'update']);
            Route::patch('schedules/{schedule}/stations/{schedule_station}', [ScheduleController::class, 'updateStation']);
            Route::patch('schedules/{schedule}/stations/by-station/{station}', [ScheduleController::class, 'updateStationByStation']);

            Route::post('stations', [StationController::class, 'store']);
            Route::get('stations/{station}', [StationController::class, 'show']);
            Route::put('stations/{station}', [StationController::class, 'update']);
            Route::patch('stations/{station}', [StationController::class, 'update']);
            Route::delete('stations/{station}', [StationController::class, 'destroy']);
        });
    });

    // Serve the static OpenAPI JSON, with the generated file as a fallback if needed
    Route::get('/docs/swagger.json', function () {
        $path = public_path('docs/swagger.json');

        if (! file_exists($path)) {
            $path = storage_path('api-docs/api-docs.json');
        }

        if (! file_exists($path)) {
            return response()->json(['error' => 'API docs not found'], 404);
        }
        return response()->file($path, ['Content-Type' => 'application/json']);
    });

    // Swagger UI view (loads the JSON above)
    Route::get('/docs', function () {
        return response()->view('swagger');
    });
});
