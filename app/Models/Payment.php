<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'tutor_request_id', 'booking_id', 'session_id', 'parent_id', 'amount', 'commission_amount',
        'tutor_payout', 'payment_method', 'gateway', 'transaction_id', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'tutor_payout' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function tutorRequest()
    {
        return $this->belongsTo(TutorRequest::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function session()
    {
        return $this->belongsTo(TutorSession::class, 'session_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
}
