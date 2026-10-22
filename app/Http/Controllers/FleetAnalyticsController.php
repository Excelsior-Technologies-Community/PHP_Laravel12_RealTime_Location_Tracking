<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Geofence;
use App\Models\GeofenceEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FleetAnalyticsController extends Controller
{
    /**
     * Display Fleet Telemetry Analytics Dashboard
     */
    public function index()
    {
        $totalPings = Location::count();
        $activeVehiclesCount = Location::whereNotNull('vehicle_id')->distinct('vehicle_id')->count();
        $totalGeofences = Geofence::count();
        $totalBreaches = GeofenceEvent::count();

        $avgSpeed = round((float) (Location::where('speed', '>', 0)->avg('speed') ?? 35), 1);
        $maxSpeed = Location::max('speed') ?? 0;

        // Speed Distribution Buckets
        $speedBuckets = [
            'Idle (0-5 km/h)' => Location::where('speed', '<=', 5)->count(),
            'City (5-30 km/h)' => Location::whereBetween('speed', [5.01, 30])->count(),
            'Cruising (30-60 km/h)' => Location::whereBetween('speed', [30.01, 60])->count(),
            'Highway (60+ km/h)' => Location::where('speed', '>', 60)->count(),
        ];

        // Vehicle Mileage & Points Leaderboard
        $vehicleStats = Location::whereNotNull('vehicle_id')
            ->select('vehicle_id', 'user_name', 'vehicle_type', DB::raw('count(*) as pings'), DB::raw('AVG(speed) as avg_speed'), DB::raw('MAX(speed) as top_speed'))
            ->groupBy('vehicle_id', 'user_name', 'vehicle_type')
            ->orderByDesc('pings')
            ->get();

        // 14-Day Ping Activity
        $dailyRecords = Location::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $timelineLabels = [];
        $timelineData = [];

        for ($i = 13; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $timelineLabels[] = date('M d', strtotime($d));
            $timelineData[] = $dailyRecords[$d] ?? 0;
        }

        // Recent Geofence Breaches
        $recentBreaches = GeofenceEvent::with('geofence')->latest('id')->take(6)->get();

        return view('fleet.analytics', compact(
            'totalPings',
            'activeVehiclesCount',
            'totalGeofences',
            'totalBreaches',
            'avgSpeed',
            'maxSpeed',
            'speedBuckets',
            'vehicleStats',
            'timelineLabels',
            'timelineData',
            'recentBreaches'
        ));
    }

    /**
     * Export Full Telemetry History CSV
     */
    public function exportCsv(): StreamedResponse
    {
        $fileName = 'fleet_telemetry_' . now()->format('Y_m_d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID',
                'Vehicle ID',
                'Driver / Name',
                'Vehicle Type',
                'Latitude',
                'Longitude',
                'Speed (km/h)',
                'Heading (deg)',
                'Battery (%)',
                'Trip ID',
                'Timestamp'
            ]);

            Location::orderBy('id')->chunk(300, function ($locations) use ($file) {
                foreach ($locations as $loc) {
                    fputcsv($file, [
                        $loc->id,
                        $loc->vehicle_id ?: 'N/A',
                        $loc->user_name,
                        $loc->vehicle_type ?: 'car',
                        $loc->latitude,
                        $loc->longitude,
                        $loc->speed,
                        $loc->heading,
                        $loc->battery_level,
                        $loc->trip_id,
                        $loc->created_at,
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
