<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Geofence;
use App\Models\GeofenceEvent;
use App\Events\LocationEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FleetRadarController extends Controller
{
    /**
     * Display Multi-Vehicle Fleet Radar & Status Grid
     */
    public function index()
    {
        $vehicles = $this->getFleetVehicles();
        $geofences = Geofence::where('is_active', true)->get();

        return view('fleet.radar', compact('vehicles', 'geofences'));
    }

    /**
     * API: Get latest status and location of all fleet vehicles
     */
    public function apiVehicles()
    {
        $vehicles = $this->getFleetVehicles();
        $geofences = Geofence::where('is_active', true)->get();

        return response()->json([
            'success' => true,
            'count' => count($vehicles),
            'vehicles' => $vehicles,
            'geofences' => $geofences,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * API: Process live GPS ping from vehicle & check geofences
     */
    public function apiPing(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|string',
            'user_name' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|integer',
            'battery_level' => 'nullable|integer',
            'vehicle_type' => 'nullable|string',
        ]);

        $location = Location::create([
            'user_name' => $request->user_name,
            'vehicle_id' => $request->vehicle_id,
            'vehicle_type' => $request->vehicle_type ?? 'car',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'speed' => $request->speed ?? 0,
            'heading' => $request->heading ?? 0,
            'battery_level' => $request->battery_level ?? 100,
            'trip_id' => $request->trip_id ?? 'TRIP-' . date('Ymd'),
            'status' => 'Online',
            'last_seen' => now(),
        ]);

        // Evaluate Geofences
        $activeGeofences = Geofence::where('is_active', true)->get();
        $breaches = [];

        foreach ($activeGeofences as $gf) {
            if ($gf->containsPoint($location->latitude, $location->longitude)) {
                $lastEvent = GeofenceEvent::where('geofence_id', $gf->id)
                    ->where('vehicle_id', $location->vehicle_id)
                    ->latest('occurred_at')
                    ->first();

                if (!$lastEvent || $lastEvent->event_type === 'EXIT') {
                    $event = GeofenceEvent::create([
                        'geofence_id' => $gf->id,
                        'vehicle_id' => $location->vehicle_id,
                        'user_name' => $location->user_name,
                        'event_type' => 'ENTER',
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                        'speed' => $location->speed,
                        'occurred_at' => now(),
                    ]);
                    $breaches[] = [
                        'type' => 'ENTER',
                        'geofence' => $gf->name,
                        'color' => $gf->color,
                    ];
                }
            } else {
                $lastEvent = GeofenceEvent::where('geofence_id', $gf->id)
                    ->where('vehicle_id', $location->vehicle_id)
                    ->latest('occurred_at')
                    ->first();

                if ($lastEvent && $lastEvent->event_type === 'ENTER') {
                    $event = GeofenceEvent::create([
                        'geofence_id' => $gf->id,
                        'vehicle_id' => $location->vehicle_id,
                        'user_name' => $location->user_name,
                        'event_type' => 'EXIT',
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude,
                        'speed' => $location->speed,
                        'occurred_at' => now(),
                    ]);
                    $breaches[] = [
                        'type' => 'EXIT',
                        'geofence' => $gf->name,
                        'color' => $gf->color,
                    ];
                }
            }
        }

        try {
            broadcast(new LocationEvent($location))->toOthers();
        } catch (\Throwable $e) {
            // Broadcasting fallback
        }

        return response()->json([
            'success' => true,
            'location' => $location,
            'breaches' => $breaches,
        ]);
    }

    /**
     * API: Advance all simulated vehicles by 1 GPS step
     */
    public function apiSimulatorTick()
    {
        $vehicleIds = ['VH-101', 'VH-102', 'VH-103', 'VH-104'];
        $updated = [];

        foreach ($vehicleIds as $vId) {
            $latest = Location::where('vehicle_id', $vId)->latest('id')->first();
            if (!$latest) continue;

            $angle = ($latest->heading + rand(-15, 15) + 360) % 360;
            $rad = deg2rad($angle);
            $distMeters = rand(15, 45); // move 15 to 45 meters
            $deltaLat = ($distMeters * cos($rad)) / 111320;
            $deltaLng = ($distMeters * sin($rad)) / (111320 * cos(deg2rad($latest->latitude)));

            $newLat = round($latest->latitude + $deltaLat, 6);
            $newLng = round($latest->longitude + $deltaLng, 6);
            $newSpeed = max(10, min(85, $latest->speed + rand(-6, 8)));

            $newLoc = Location::create([
                'user_name' => $latest->user_name,
                'vehicle_id' => $latest->vehicle_id,
                'vehicle_type' => $latest->vehicle_type,
                'latitude' => $newLat,
                'longitude' => $newLng,
                'speed' => $newSpeed,
                'heading' => $angle,
                'battery_level' => max(5, $latest->battery_level - 1),
                'trip_id' => $latest->trip_id,
                'status' => 'Online',
                'last_seen' => now(),
            ]);

            $updated[] = $newLoc;
        }

        return response()->json([
            'success' => true,
            'updated_count' => count($updated),
            'vehicles' => $this->getFleetVehicles(),
        ]);
    }

    /**
     * Helper to retrieve latest location for every distinct vehicle
     */
    private function getFleetVehicles()
    {
        $vehicleIds = Location::whereNotNull('vehicle_id')->distinct()->pluck('vehicle_id');
        $fleet = [];

        foreach ($vehicleIds as $id) {
            $latest = Location::where('vehicle_id', $id)->latest('id')->first();
            if ($latest) {
                // Determine motion status
                $diffSec = now()->diffInSeconds($latest->last_seen);
                if ($diffSec > 600) {
                    $motionStatus = 'Offline';
                    $statusColor = 'secondary';
                } elseif ($latest->speed > 5) {
                    $motionStatus = 'Moving';
                    $statusColor = 'success';
                } elseif ($diffSec > 180) {
                    $motionStatus = 'Idle';
                    $statusColor = 'warning';
                } else {
                    $motionStatus = 'Stopped';
                    $statusColor = 'danger';
                }

                $fleet[] = [
                    'vehicle_id' => $latest->vehicle_id,
                    'user_name' => $latest->user_name,
                    'vehicle_type' => $latest->vehicle_type,
                    'latitude' => $latest->latitude,
                    'longitude' => $latest->longitude,
                    'speed' => $latest->speed,
                    'heading' => $latest->heading,
                    'battery_level' => $latest->battery_level,
                    'trip_id' => $latest->trip_id,
                    'motion_status' => $motionStatus,
                    'status_color' => $statusColor,
                    'last_seen' => $latest->last_seen ? $latest->last_seen->diffForHumans() : 'Just now',
                    'updated_at' => $latest->created_at->format('H:i:s'),
                ];
            }
        }

        return $fleet;
    }
}
