<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeofenceEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'geofence_id',
        'vehicle_id',
        'user_name',
        'event_type',
        'latitude',
        'longitude',
        'speed',
        'occurred_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'speed' => 'float',
        'occurred_at' => 'datetime',
    ];

    public function geofence()
    {
        return $this->belongsTo(Geofence::class);
    }
}
