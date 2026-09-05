<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\TutorSession;
use App\Support\SessionScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $query = TutorSession::whereHas('booking', fn ($q) => $q->where('tutor_id', auth()->id()))
            ->with(['booking.parent', 'booking.student', 'booking.subject']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->orderBy('session_date', 'desc')->orderBy('start_time', 'desc')->paginate(15);

        return Inertia::render('Tutor/Sessions/Index', [
            'sessions' => $sessions,
            'filters' => $request->only('status'),
        ]);
    }

    public function show(TutorSession $session)
    {
        abort_unless($session->booking->tutor_id === auth()->id(), 403);

        $session->load(['booking.parent', 'booking.student', 'booking.subject']);

        return Inertia::render('Tutor/Sessions/Show', [
            'session' => $session,
        ]);
    }

    public function checkIn(Request $request, TutorSession $session)
    {
        abort_unless($session->booking->tutor_id === auth()->id(), 403);
        abort_unless($session->status === 'scheduled', 403);

        $request->validate([
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $session->update([
            'checked_in_at' => now(),
            'check_in_lat' => $request->lat,
            'check_in_lng' => $request->lng,
            'check_in_method' => 'qr',
            'status' => 'checked_in',
        ]);

        return back()->with('success', 'Checked in successfully.');
    }

    public function uploadProof(Request $request, TutorSession $session)
    {
        abort_unless($session->booking->tutor_id === auth()->id(), 403);
        abort_unless(in_array($session->status, ['checked_in', 'scheduled']), 403);

        $request->validate([
            'photo' => 'required|image|max:5120',
        ]);

        $path = $request->file('photo')->store('proof-photos/'.$session->id, 'public');

        $photos = $session->proof_photos ?? [];
        $photos[] = '/storage/'.$path;

        $session->update(['proof_photos' => $photos]);

        return back()->with('success', 'Photo uploaded successfully.');
    }

    public function removeProof(Request $request, TutorSession $session)
    {
        abort_unless($session->booking->tutor_id === auth()->id(), 403);

        $request->validate([
            'index' => 'required|integer|min:0',
        ]);

        $photos = $session->proof_photos ?? [];
        $index = $request->index;

        if (isset($photos[$index])) {
            $filePath = str_replace('/storage/', '', $photos[$index]);
            Storage::disk('public')->delete($filePath);
            array_splice($photos, $index, 1);
            $session->update(['proof_photos' => $photos]);
        }

        return back()->with('success', 'Photo removed.');
    }

    public function checkOut(Request $request, TutorSession $session)
    {
        abort_unless($session->booking->tutor_id === auth()->id(), 403);
        abort_unless($session->status === 'checked_in', 403);

        // Require at least one proof photo
        if (empty($session->proof_photos)) {
            return back()->with('error', 'Please upload at least one proof photo before checking out.');
        }

        $request->validate([
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'tutor_notes' => 'nullable|string|max:1000',
        ]);

        $duration = $session->checked_in_at->diffInMinutes(now());

        $session->update([
            'checked_out_at' => now(),
            'check_out_lat' => $request->lat,
            'check_out_lng' => $request->lng,
            'duration_minutes' => $duration,
            'tutor_notes' => $request->tutor_notes,
            'status' => 'completed',
        ]);

        // Credit per-session earnings from upfront package payment
        $booking = $session->booking;
        $tutorRequest = $booking->tutorRequest;
        $upfrontPayment = $tutorRequest?->payment;
        $totalSessions = $tutorRequest?->package?->total_sessions ?? 1;

        if ($upfrontPayment && $upfrontPayment->status === 'success') {
            $perSessionAmount = floatval($upfrontPayment->amount) / $totalSessions;
            $perSessionCommission = floatval($upfrontPayment->commission_amount) / $totalSessions;
            $perSessionPayout = floatval($upfrontPayment->tutor_payout) / $totalSessions;

            Payment::create([
                'tutor_request_id' => $tutorRequest->id,
                'booking_id' => $booking->id,
                'session_id' => $session->id,
                'parent_id' => $booking->parent_id,
                'amount' => round($perSessionAmount, 2),
                'commission_amount' => round($perSessionCommission, 2),
                'tutor_payout' => round($perSessionPayout, 2),
                'payment_method' => $upfrontPayment->payment_method,
                'status' => 'success',
                'paid_at' => now(),
            ]);
        }

        return back()->with('success', "Checked out. Duration: {$duration} minutes.");
    }

    /**
     * Top a booking up to the sessions its package promises.
     *
     * Sessions are now laid out when the booking is created, so this is a
     * repair for bookings that predate that, or an extension when a package
     * grows. It counts what already exists, so pressing it twice is harmless.
     */
    public function generateSessions(Request $request, Booking $booking)
    {
        abort_unless($booking->tutor_id === auth()->id(), 403);

        $scheduler = app(SessionScheduler::class);
        $target = $scheduler->targetFor($booking);

        if (blank($booking->schedule_day)) {
            return back()->with('error', 'This booking has no weekly schedule, so sessions cannot be laid out.');
        }

        $created = $scheduler->ensure($booking, $target);

        return back()->with('success', $created === 0
            ? "All {$target} session(s) for this booking already exist."
            : "{$created} session(s) generated. This booking now has {$target} in total.");
    }
}
