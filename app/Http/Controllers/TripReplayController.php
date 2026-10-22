<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class TripReplayController extends Controller
{
    /**
     * Display Trip Replay & Route Trail Studio
     */
    public function index(Request $request)
    {
        $vehicles = Location::whereNotNull('vehicle_id')
            ->distinct()
            ->pluck('vehicle_id');

        $selectedVehicle = $request->query('vehicle_id', $vehicles->first() ?? 'VH-101');

        $trips = Location::where('vehicle_id', $selectedVehicle)
            ->whereNotNull('trip_id')
            ->distinct()
            ->pluck('trip_id');

        $selectedTrip = $request->query('trip_id', $trips->first() ?? '');

        return view('fleet.replay', compact('vehicles', 'selectedVehicle', 'trips', 'selectedTrip'));
    }

    /**
     * API: Get ordered GPS waypoints for an entire trip
     */
    public function getWaypoints(Request $request)
    {
        $vehicleId = $request->query('vehicle_id');
        $tripId = $request->query('trip_id');

        $query = Location::query();
        if ($vehicleId) {
            $query->where('vehicle_id', $vehicleId);
        }
        if ($tripId) {
            $query->where('trip_id', $tripId);
        }

        $points = $query->orderBy('created_at', 'asc')->get();

        $totalDistanceMeters = 0;
        $waypoints = [];

        for ($i = 0; $i < count($points); $i++) {
            $p = $points[$i];

            if ($i > 0) {
                $prev = $points[$i - 1];
                $dist = $this->haversineDistance($prev->latitude, $prev->longitude, $p->latitude, $p->longitude);
                $totalDistanceMeters += $dist;
            }

            $waypoints[] = [
                'id' => $p->id,
                'lat' => $p->latitude,
                'lng' => $p->longitude,
                'speed' => $p->speed,
                'heading' => $p->heading,
                'battery' => $p->battery_level,
                'timestamp' => $p->created_at->format('H:i:s'),
                'datetime' => $p->created_at->toIso8601String(),
                'distance_so_far_km' => round($totalDistanceMeters / 1000, 2),
            ];
        }

        return response()->json([
            'success' => true,
            'vehicle_id' => $vehicleId,
            'trip_id' => $tripId,
            'total_points' => count($waypoints),
            'total_distance_km' => round($totalDistanceMeters / 1000, 2),
            'waypoints' => $waypoints,
        ]);
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
