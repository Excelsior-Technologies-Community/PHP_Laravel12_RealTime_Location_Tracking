<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Events\LocationEvent;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Dashboard
     */
    public function index(Request $request)
    {
        $search = $request->search;

        $locations = Location::when($search, function ($query) use ($search) {
            $query->where('user_name', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('latitude', 'like', "%{$search}%")
                ->orWhere('longitude', 'like', "%{$search}%");
        })
            ->oldest()
            ->paginate(6)
            ->withQueryString();

        return view('map', compact('locations', 'search'));
    }

    /**
     * Save Live Location
     */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'action' => 'required|in:start,move,stop',
        ]);

        // Get latest location
        $lastLocation = Location::latest()->first();

        // Save first record
        if (!$lastLocation) {

            $location = Location::create([
                'user_name' => 'User ' . rand(100, 999),
                'latitude'   => $validated['lat'],
                'longitude'  => $validated['lng'],
                'status'     => 'Online',
                'last_seen'  => now(),
            ]);

            broadcast(new LocationEvent($location))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'First location saved.',
                'data'    => $location,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Save Start / Stop Events
        |--------------------------------------------------------------------------
        */

        if ($lastLocation && in_array($validated['action'], ['start', 'stop'])) {

            $location = Location::create([
                'user_name'  => $lastLocation->user_name,
                'latitude'   => $validated['lat'],
                'longitude'  => $validated['lng'],
                'status'     => $validated['action'] === 'start'
                    ? 'Online'
                    : 'Offline',
                'last_seen'  => now(),
            ]);

            broadcast(new LocationEvent($location))->toOthers();

            return response()->json([
                'success' => true,
                'message' => ucfirst($validated['action']) . ' location saved.',
                'data'    => $location,
            ]);
        }

    /*
    |--------------------------------------------------------------------------
    | Calculate Distance (Meters)
    |--------------------------------------------------------------------------
    */

        $earthRadius = 6371000;

        $latFrom = deg2rad($lastLocation->latitude);
        $lonFrom = deg2rad($lastLocation->longitude);

        $latTo = deg2rad($validated['lat']);
        $lonTo = deg2rad($validated['lng']);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
                cos($latFrom) *
                cos($latTo) *
                pow(sin($lonDelta / 2), 2)
        ));

        $distance = $angle * $earthRadius;

        /*
        |--------------------------------------------------------------------------
        | Save only if user moved 30 meters
        |--------------------------------------------------------------------------
        */

        if ($distance >= 30) {

            $location = Location::create([
                'user_name' => $lastLocation->user_name,
                'latitude' => $validated['lat'],
                'longitude' => $validated['lng'],
                'status' => 'Online',
                'last_seen' => now(),
            ]);

            broadcast(new LocationEvent($location))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Location saved (' . round($distance) . ' meters moved).',
                'data' => $location,
            ]);
        }
        /*
    |--------------------------------------------------------------------------
    | Don't Save Duplicate Location
    |--------------------------------------------------------------------------
    */

        return response()->json([
            'success' => true,
            'message' => 'Movement less than 30 meters. Record not saved.',
            'distance' => round($distance, 2),
        ]);
    }

    /**
     * Delete a single location record.
     */
    public function destroy($id)
    {
        $location = Location::findOrFail($id);

        $location->delete();

        return redirect()->back()->with(
            'success',
            'Location history deleted successfully.'
        );
    }

    /**
     * Delete all location history.
     */
    public function deleteAll()
    {
        Location::truncate();

        return redirect()->back()->with(
            'success',
            'All location history deleted successfully.'
        );
    }

    /**
     * Export Location History to CSV
     */
    public function exportCsv(): StreamedResponse
    {
        $fileName = 'location_history_' . now()->format('Y_m_d_H_i_s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () {

            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID',
                'User Name',
                'Latitude',
                'Longitude',
                'Status',
                'Last Seen',
                'Created At'
            ]);

            Location::orderBy('id')->chunk(200, function ($locations) use ($file) {

                foreach ($locations as $location) {
                    fputcsv($file, [
                        $location->id,
                        $location->user_name,
                        $location->latitude,
                        $location->longitude,
                        $location->status,
                        $location->last_seen,
                        $location->created_at,
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Update User Status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Online,Offline',
        ]);

        $location = Location::findOrFail($id);

        $location->update([
            'status' => $request->status,
            'last_seen' => now(),
        ]);

        broadcast(new LocationEvent($location))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'status' => $location->status,
        ]);
    }
}
