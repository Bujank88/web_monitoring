<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationPresensiCvsr extends Model
{
    protected $table = 'location_presensi_cvsr';
    
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'keterangan',
        'is_location_needed',
    ];
    
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_location_needed' => 'boolean',
    ];
    
    /**
     * Relationship: lokasi ini milik CVSR user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Calculate distance in meters between two coordinates
     * Using Haversine formula
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371000; // Radius Bumi dalam meter
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $R * $c; // Distance in meters
    }
}
