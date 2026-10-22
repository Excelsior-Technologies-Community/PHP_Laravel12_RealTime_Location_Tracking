<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Location extends Model
{
    protected $fillable = [
        'user_name',
        'vehicle_id',
        'vehicle_type',
        'latitude',
        'longitude',
        'speed',
        'heading',
        'battery_level',
        'trip_id',
        'status',
        'last_seen',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'speed' => 'float',
        'heading' => 'integer',
        'battery_level' => 'integer',
    ];

    /**
     * Scope for searching locations.
     */
    public function scopeSearch(Builder $query, $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('user_name', 'like', "%{$search}%")
                ->orWhere('vehicle_id', 'like', "%{$search}%")
                ->orWhere('latitude', 'like', "%{$search}%")
                ->orWhere('longitude', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%");
        });
    }

    /**
     * Check if the user is online.
     */
    public function isOnline()
    {
        return $this->status === 'Online';
    }

    /**
     * Get human-readable vehicle icon class.
     */
    public function getVehicleIconAttribute()
    {
        return match ($this->vehicle_type) {
            'bike' => 'bi-bicycle',
            'truck' => 'bi-truck',
            'ambulance' => 'bi-hospital',
            default => 'bi-car-front-fill',
        };
    }
}
