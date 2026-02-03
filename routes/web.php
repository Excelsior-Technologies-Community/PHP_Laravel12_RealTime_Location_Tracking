<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LocationController;

Route::get('/', [LocationController::class, 'index']);
Route::post('/location', [LocationController::class, 'store']);
