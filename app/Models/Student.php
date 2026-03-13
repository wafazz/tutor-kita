<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'age', 'school', 'education_level', 'notes',
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
