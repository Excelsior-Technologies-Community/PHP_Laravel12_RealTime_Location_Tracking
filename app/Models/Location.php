<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Location extends Model
{
    protected $fillable = [
        'user_name',
        'latitude',
        'longitude',
        'status',
        'last_seen',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
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
     * Get formatted last seen.
     */
    public function getLastSeenAttribute($value)
    {
        return $value
            ? \Carbon\Carbon::parse($value)->format('d M Y h:i A')
            : '-';
    }

    /**
     * Get badge class for status.
     */
    public function getStatusColorAttribute()
    {
        return $this->status === 'Online'
            ? 'success'
            : 'secondary';
    }
}
