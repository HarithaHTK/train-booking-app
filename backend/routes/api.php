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
use App\Http\Controllers\RouteStationController;
use App\Http\Controllers\StationController;
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

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        // Dashboard is admin-only
        Route::get('/dashboard', [AuthController::class, 'dashboard'])
            ->middleware(\App\Http\Middleware\RequireRole::class . ':admin');

        Route::middleware(\App\Http\Middleware\RequireRole::class . ':admin')
            ->apiResource('routes', TrainRouteController::class);

        Route::middleware(\App\Http\Middleware\RequireRole::class . ':admin')
            ->apiResource('route-stations', RouteStationController::class);

        Route::middleware(\App\Http\Middleware\RequireRole::class . ':admin')
            ->apiResource('stations', StationController::class);
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
