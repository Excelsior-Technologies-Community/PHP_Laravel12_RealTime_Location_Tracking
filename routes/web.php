<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Dashboard
Route::get('/', [LocationController::class, 'index'])->name('dashboard');

// Save Live Location
Route::post('/location', [LocationController::class, 'store'])->name('location.store');

// Location History
Route::get('/history', [LocationController::class, 'history'])->name('history');

// Delete Single History
Route::delete('/history/{id}', [LocationController::class, 'destroy'])->name('history.delete');

// Delete All History
Route::delete('/history', [LocationController::class, 'deleteAll'])->name('history.deleteAll');

// Export CSV
Route::get('/export-csv', [LocationController::class, 'exportCsv'])->name('location.export');

// Update Online / Offline Status
Route::post('/status/{id}', [LocationController::class, 'updateStatus'])->name('location.status');
