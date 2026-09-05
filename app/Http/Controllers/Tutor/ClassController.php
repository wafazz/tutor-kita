<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use Inertia\Inertia;

class ClassController extends Controller
{
    public function index()
    {
        $classes = ClassSession::where('tutor_id', auth()->id())
            ->whereNotIn('status', ['cancelled'])
            ->with(['subject:id,name', 'centre:id,name,area', 'enrolments.student:id,name'])
            ->get()
            // Ordered in PHP: FIELD() is MySQL-only and the tests run on SQLite.
            ->sortBy(fn (ClassSession $c) => array_search(
                $c->status, ['open', 'closed', 'draft', 'completed'], true
            ) ?: 99)
            ->values()
            ->map(fn (ClassSession $c) => [
                'id' => $c->id,
                'title' => $c->title ?? $c->subject?->name,
                'subject_name' => $c->subject?->name,
                'centre_name' => $c->centre?->name,
                'centre_area' => $c->centre?->area,
                'is_online' => $c->delivery_mode->isOnline(),
                'schedule_day' => $c->schedule_day,
                'schedule_time' => $c->schedule_time,
                'duration_hours' => $c->duration_hours,
                'total_sessions' => $c->total_sessions,
                'status' => $c->status,
                'seats_taken' => $c->seatsTaken(),
                'seats_left' => $c->seatsLeft(),
                // What this class is worth to them, and how they are paid for
                // it — a flat class earns the same whoever turns up, and a
                // tutor should be able to see which arrangement applies.
                'payout_model' => $c->payout_model->value,
                'payout_label' => $c->payout_model->label(),
                'earns' => $c->tutorPayoutTotal(),
                'students' => $c->enrolments
                    ->whereIn('status', ['pending', 'active'])
                    ->map(fn ($e) => ['name' => $e->student?->name, 'confirmed' => $e->status === 'active'])
                    ->values(),
            ]);

        return Inertia::render('Tutor/Classes/Index', [
            'classes' => $classes,
        ]);
    }
}
