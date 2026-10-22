<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Geofence;
use App\Models\GeofenceEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FleetLocationSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        Location::query()->delete();
        Geofence::query()->delete();
        GeofenceEvent::query()->delete();

        // 1. Seed Active Geofences around Ahmedabad / Metro Tech Hub
        $gf1 = Geofence::create([
            'name' => 'Central Logistics Warehouse',
            'type' => 'circle',
            'latitude' => 23.0225,
            'longitude' => 72.5714,
            'radius_meters' => 800,
            'color' => '#10b981', // Green
            'alert_on_entry' => true,
            'alert_on_exit' => true,
            'is_active' => true,
        ]);

        $gf2 = Geofence::create([
            'name' => 'Airport Express Corridor',
            'type' => 'circle',
            'latitude' => 23.0734,
            'longitude' => 72.6266,
            'radius_meters' => 1200,
            'color' => '#3b82f6', // Blue
            'alert_on_entry' => true,
            'alert_on_exit' => true,
            'is_active' => true,
        ]);

        $gf3 = Geofence::create([
            'name' => 'High-Security Restricted Zone',
            'type' => 'circle',
            'latitude' => 23.0450,
            'longitude' => 72.5350,
            'radius_meters' => 600,
            'color' => '#ef4444', // Red
            'alert_on_entry' => true,
            'alert_on_exit' => true,
            'is_active' => true,
        ]);

        // 2. Seed 4 Fleet Vehicles with Complete Sequential Trips for Replay
        $vehicles = [
            [
                'id' => 'VH-101',
                'name' => 'Express Courier #101',
                'type' => 'bike',
                'trip_id' => 'TRIP-DELIVERY-01',
                'battery' => 88,
                'start_lat' => 23.0220,
                'start_lng' => 72.5710,
                'delta_lat' => 0.0008,
                'delta_lng' => 0.0006,
                'base_speed' => 38,
            ],
            [
                'id' => 'VH-102',
                'name' => 'City Taxi Cab #102',
                'type' => 'car',
                'trip_id' => 'TRIP-AIRPORT-02',
                'battery' => 95,
                'start_lat' => 23.0550,
                'start_lng' => 72.6000,
                'delta_lat' => 0.0012,
                'delta_lng' => 0.0015,
                'base_speed' => 54,
            ],
            [
                'id' => 'VH-103',
                'name' => 'Heavy Freight Hauler #103',
                'type' => 'truck',
                'trip_id' => 'TRIP-CARGO-03',
                'battery' => 74,
                'start_lat' => 23.0100,
                'start_lng' => 72.5400,
                'delta_lat' => 0.0006,
                'delta_lng' => 0.0009,
                'base_speed' => 42,
            ],
            [
                'id' => 'VH-104',
                'name' => 'Emergency Response #104',
                'type' => 'ambulance',
                'trip_id' => 'TRIP-MEDIC-04',
                'battery' => 99,
                'start_lat' => 23.0380,
                'start_lng' => 72.5650,
                'delta_lat' => 0.0015,
                'delta_lng' => 0.0008,
                'base_speed' => 68,
            ],
        ];

        foreach ($vehicles as $v) {
            $lat = $v['start_lat'];
            $lng = $v['start_lng'];
            $heading = rand(30, 120);

            // Generate 25 historical waypoints per vehicle for smooth playback
            for ($step = 0; $step < 25; $step++) {
                $lat += $v['delta_lat'] + (rand(-10, 10) / 100000);
                $lng += $v['delta_lng'] + (rand(-10, 10) / 100000);
                $speed = max(15, min(85, $v['base_speed'] + rand(-8, 12)));
                $heading = ($heading + rand(-10, 10) + 360) % 360;
                $timestamp = now()->subMinutes((25 - $step) * 2);

                Location::create([
                    'user_name' => $v['name'],
                    'vehicle_id' => $v['id'],
                    'vehicle_type' => $v['type'],
                    'latitude' => round($lat, 6),
                    'longitude' => round($lng, 6),
                    'speed' => $speed,
                    'heading' => $heading,
                    'battery_level' => max(10, $v['battery'] - round($step * 0.4)),
                    'trip_id' => $v['trip_id'],
                    'status' => 'Online',
                    'last_seen' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        }

        // 3. Seed Realistic Geofence Events
        GeofenceEvent::create([
            'geofence_id' => $gf1->id,
            'vehicle_id' => 'VH-101',
            'user_name' => 'Express Courier #101',
            'event_type' => 'ENTER',
            'latitude' => 23.0225,
            'longitude' => 72.5714,
            'speed' => 32.5,
            'occurred_at' => now()->subMinutes(35),
        ]);

        GeofenceEvent::create([
            'geofence_id' => $gf1->id,
            'vehicle_id' => 'VH-101',
            'user_name' => 'Express Courier #101',
            'event_type' => 'EXIT',
            'latitude' => 23.0298,
            'longitude' => 72.5780,
            'speed' => 44.0,
            'occurred_at' => now()->subMinutes(20),
        ]);

        GeofenceEvent::create([
            'geofence_id' => $gf2->id,
            'vehicle_id' => 'VH-102',
            'user_name' => 'City Taxi Cab #102',
            'event_type' => 'ENTER',
            'latitude' => 23.0734,
            'longitude' => 72.6266,
            'speed' => 58.2,
            'occurred_at' => now()->subMinutes(12),
        ]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
