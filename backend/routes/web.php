<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Short docs redirect to the L5-Swagger UI
Route::get('/docs', function () {
    return redirect('/api/documentation');
});
