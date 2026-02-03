<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Events\LocationEvent;

class LocationController extends Controller
{
    public function index()
    {
        return view('map');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $location = Location::create([
            'user_name' => 'User ' . rand(100, 999),
            'latitude' => $validated['lat'],
            'longitude' => $validated['lng'],
        ]);

        // Broadcast to all clients
        broadcast(new LocationEvent($location));

        return response()->json([
            'status' => 'success',
            'message' => 'Location saved',
            'data' => $location
        ]);
    }
}

