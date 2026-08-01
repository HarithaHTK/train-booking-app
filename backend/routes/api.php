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

    // Serve static OpenAPI JSON
    Route::get('/docs/swagger.json', function () {
        $path = public_path('docs/swagger.json');
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
