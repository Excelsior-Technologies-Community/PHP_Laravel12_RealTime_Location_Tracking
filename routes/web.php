<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\FleetRadarController;
use App\Http\Controllers\GeofenceController;
use App\Http\Controllers\TripReplayController;
use App\Http\Controllers\FleetAnalyticsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Default redirect to Fleet Radar or original single-user map
Route::get('/', [LocationController::class, 'index'])->name('dashboard');

// Save Live Location (Original Single User)
Route::post('/location', [LocationController::class, 'store'])->name('location.store');

// Location History
Route::get('/history', [LocationController::class, 'history'])->name('history');
Route::delete('/history/{id}', [LocationController::class, 'destroy'])->name('history.delete');
Route::delete('/history', [LocationController::class, 'deleteAll'])->name('history.deleteAll');
Route::get('/export-csv', [LocationController::class, 'exportCsv'])->name('location.export');
Route::post('/status/{id}', [LocationController::class, 'updateStatus'])->name('location.status');

/*
|--------------------------------------------------------------------------
| Feature 1 & 6: Multi-Vehicle Fleet Radar & Dual Map Engine (Leaflet + Google)
|--------------------------------------------------------------------------
*/
Route::get('/fleet/radar', [FleetRadarController::class, 'index'])
    ->name('fleet.radar');

Route::get('/api/fleet/vehicles', [FleetRadarController::class, 'apiVehicles'])
    ->name('api.fleet.vehicles');

Route::post('/api/fleet/ping', [FleetRadarController::class, 'apiPing'])
    ->name('api.fleet.ping');

Route::post('/api/fleet/simulator/tick', [FleetRadarController::class, 'apiSimulatorTick'])
    ->name('api.fleet.simulator.tick');

/*
|--------------------------------------------------------------------------
| Feature 2: Interactive Geofencing Engine & Breach Alert Studio
|--------------------------------------------------------------------------
*/
Route::get('/geofences', [GeofenceController::class, 'index'])
    ->name('geofences.index');

Route::post('/geofences', [GeofenceController::class, 'store'])
    ->name('geofences.store');

Route::post('/geofences/{id}/toggle', [GeofenceController::class, 'toggle'])
    ->name('geofences.toggle');

Route::delete('/geofences/{id}', [GeofenceController::class, 'destroy'])
    ->name('geofences.destroy');

Route::post('/geofences/events/clear', [GeofenceController::class, 'clearEvents'])
    ->name('geofences.clearEvents');

/*
|--------------------------------------------------------------------------
| Feature 3: Historical Trip Replay & Route Trail Playback Studio
|--------------------------------------------------------------------------
*/
Route::get('/trip-replay', [TripReplayController::class, 'index'])
    ->name('trip.replay');

Route::get('/api/trip/waypoints', [TripReplayController::class, 'getWaypoints'])
    ->name('api.trip.waypoints');

/*
|--------------------------------------------------------------------------
| Feature 7: Fleet Telemetry Analytics & Mileage Performance Dashboard
|--------------------------------------------------------------------------
*/
Route::get('/fleet/analytics', [FleetAnalyticsController::class, 'index'])
    ->name('fleet.analytics');

Route::get('/fleet/analytics/export', [FleetAnalyticsController::class, 'exportCsv'])
    ->name('fleet.analytics.export');
