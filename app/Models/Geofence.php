<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Geofence extends Model
{
    protected $fillable = [
        'name',
        'type',
        'latitude',
        'longitude',
        'radius_meters',
        'polygon_coordinates',
        'color',
        'alert_on_entry',
        'alert_on_exit',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meters' => 'integer',
        'polygon_coordinates' => 'array',
        'alert_on_entry' => 'boolean',
        'alert_on_exit' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function events()
    {
        return $this->hasMany(GeofenceEvent::class);
    }

    /**
     * Check if a GPS coordinate is inside this circular geofence using Haversine formula.
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        if ($this->type === 'circle' && $this->latitude && $this->longitude) {
            $earthRadius = 6371000; // in meters
            $latFrom = deg2rad($this->latitude);
            $lonFrom = deg2rad($this->longitude);
            $latTo = deg2rad($lat);
            $lonTo = deg2rad($lng);

            $latDelta = $latTo - $latFrom;
            $lonDelta = $lonTo - $lonFrom;

            $angle = 2 * asin(sqrt(
                pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
            ));

            $distance = $angle * $earthRadius;
            return $distance <= $this->radius_meters;
        }

        // Ray casting algorithm for polygon geofence
        if ($this->type === 'polygon' && is_array($this->polygon_coordinates)) {
            $inside = false;
            $pts = $this->polygon_coordinates;
            $n = count($pts);
            if ($n < 3) return false;

            for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
                $xi = $pts[$i]['lat'] ?? $pts[$i][0];
                $yi = $pts[$i]['lng'] ?? $pts[$i][1];
                $xj = $pts[$j]['lat'] ?? $pts[$j][0];
                $yj = $pts[$j]['lng'] ?? $pts[$j][1];

                $intersect = (($yi > $lng) != ($yj > $lng))
                    && ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi);
                if ($intersect) $inside = !$inside;
            }
            return $inside;
        }

        return false;
    }
}
