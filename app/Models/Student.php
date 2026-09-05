<?php

namespace App\Models;

use App\Models\Concerns\HasCoordinates;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasCoordinates;

    protected $fillable = [
        'parent_id', 'name', 'age', 'school', 'education_level', 'notes',
        'address', 'area', 'state', 'postcode', 'latitude', 'longitude', 'geocoded_at',
    ];

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
