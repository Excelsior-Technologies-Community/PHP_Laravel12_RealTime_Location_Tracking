<?php

namespace App\Http\Controllers;

use App\Models\Geofence;
use App\Models\GeofenceEvent;
use Illuminate\Http\Request;

class GeofenceController extends Controller
{
    /**
     * Display Geofencing Engine & Breach Alert Studio
     */
    public function index()
    {
        $geofences = Geofence::withCount('events')->latest()->get();
        $recentEvents = GeofenceEvent::with('geofence')->latest('id')->take(15)->get();

        return view('fleet.geofences', compact('geofences', 'recentEvents'));
    }

    /**
     * Create New Geofence
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_meters' => 'required|integer|min:50|max:50000',
            'color' => 'nullable|string',
        ]);

        Geofence::create([
            'name' => $request->name,
            'type' => 'circle',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius_meters' => $request->radius_meters,
            'color' => $request->color ?: '#3b82f6',
            'alert_on_entry' => $request->boolean('alert_on_entry', true),
            'alert_on_exit' => $request->boolean('alert_on_exit', true),
            'is_active' => true,
        ]);

        return redirect()->route('geofences.index')->with('success', "Geofence '{$request->name}' created successfully!");
    }

    /**
     * Toggle Geofence Active State
     */
    public function toggle($id)
    {
        $gf = Geofence::findOrFail($id);
        $gf->is_active = !$gf->is_active;
        $gf->save();

        return redirect()->back()->with('success', "Geofence '{$gf->name}' is now " . ($gf->is_active ? 'Active' : 'Disabled') . ".");
    }

    /**
     * Delete Geofence
     */
    public function destroy($id)
    {
        $gf = Geofence::findOrFail($id);
        $gf->events()->delete();
        $gf->delete();

        return redirect()->back()->with('success', 'Geofence removed successfully.');
    }

    /**
     * Clear All Breach Logs
     */
    public function clearEvents()
    {
        GeofenceEvent::truncate();

        return redirect()->back()->with('success', 'Geofence breach logs cleared.');
    }
}
