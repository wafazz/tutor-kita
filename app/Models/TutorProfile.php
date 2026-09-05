<?php

namespace App\Models;

use App\Models\Concerns\HasCoordinates;
use Illuminate\Database\Eloquent\Model;

class TutorProfile extends Model
{
    use HasCoordinates;

    protected $fillable = [
        'user_id', 'ic_number', 'subjects', 'education_level', 'experience_years',
        'bio', 'hourly_rate', 'location_area', 'location_state', 'latitude', 'longitude',
        'availability', 'verification_status', 'verified_at', 'documents', 'rating_avg', 'total_sessions', 'commission_rate',
        'bank_name', 'bank_account_number', 'bank_account_name',
        'address', 'postcode', 'geocoded_at', 'travel_radius_km',
    ];

    protected function casts(): array
    {
        return [
            'subjects' => 'array',
            'availability' => 'array',
            'documents' => 'array',
            'verified_at' => 'datetime',
            'hourly_rate' => 'decimal:2',
            'rating_avg' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            // Financial PII: encrypted at rest so a database dump does not
            // expose tutors' account numbers.
            'bank_account_number' => 'encrypted',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether this tutor can actually be paid.
     */
    public function hasBankDetails(): bool
    {
        return filled($this->bank_name)
            && filled($this->bank_account_number)
            && filled($this->bank_account_name);
    }

    /**
     * Account number with all but the last four digits hidden, for screens
     * that only need to confirm which account is on file.
     */
    public function maskedAccountNumber(): ?string
    {
        $number = $this->bank_account_number;

        if (blank($number)) {
            return null;
        }

        return strlen($number) <= 4
            ? $number
            : str_repeat('•', strlen($number) - 4).substr($number, -4);
    }
}
