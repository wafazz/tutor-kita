<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostcodeCentroid extends Model
{
    protected $fillable = ['postcode', 'area', 'state', 'latitude', 'longitude'];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float'];
    }
}
