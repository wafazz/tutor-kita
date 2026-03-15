<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\TutorPayout;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayoutController extends Controller
{
    public function index(Request $request)
    {
        $query = TutorPayout::with('tutor');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payouts = $query->orderBy('created_at', 'desc')->paginate(15);

        $totals = [
            'pending' => TutorPayout::where('status', 'pending')->sum('amount'),
            'processing' => TutorPayout::where('status', 'processing')->sum('amount'),
            'paid' => TutorPayout::where('status', 'paid')->sum('amount'),
        ];

        return Inertia::render('Admin/Payouts/Index', [
            'payouts' => $payouts,
            'totals' => $totals,
            'filters' => $request->only('status'),
        ]);
    }

    public function create()
    {
        $tutors = User::where('role', 'tutor')
            ->whereHas('tutorProfile', fn ($q) => $q->where('verification_status', 'verified'))
            ->get()
            ->map(function ($tutor) {
                $unpaidAmount = Payment::where('status', 'success')
                    ->whereHas('booking', fn ($q) => $q->where('tutor_id', $tutor->id))
                    ->whereDoesntHave('booking', function ($q) use ($tutor) {
                        $q->whereHas('payments', function ($pq) use ($tutor) {
                            // this is just for filtering
                        });
                    })
                    ->sum('tutor_payout');

                // Get unpaid sessions count
                $unpaidSessions = Payment::where('status', 'success')
                    ->whereHas('booking', fn ($q) => $q->where('tutor_id', $tutor->id))
                    ->whereNull('session_id')
                    ->orWhere(function ($q) use ($tutor) {
                        $q->where('status', 'success')
                            ->whereHas('booking', fn ($bq) => $bq->where('tutor_id', $tutor->id));
                    })
                    ->count();

                return [
                    'id' => $tutor->id,
                    'name' => $tutor->name,
                    'email' => $tutor->email,
                    'unpaid_amount' => $unpaidAmount,
                ];
            })
            ->filter(fn ($t) => floatval($t['unpaid_amount']) > 0)
            ->values();

        return Inertia::render('Admin/Payouts/Create', [
            'tutors' => $tutors,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tutor_id' => 'required|exists:users,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
        ]);

        $tutorId = $request->tutor_id;

        // Calculate payout from successful payments in period
        $payments = Payment::where('status', 'success')
            ->whereHas('booking', fn ($q) => $q->where('tutor_id', $tutorId))
            ->whereHas('session', fn ($q) => $q->whereBetween('session_date', [$request->period_start, $request->period_end]))
            ->get();

        $amount = $payments->sum('tutor_payout');
        $sessionsCount = $payments->count();

        if ($amount <= 0) {
            return back()->with('error', 'No payable sessions found for this period.');
        }

        $payout = TutorPayout::create([
            'tutor_id' => $tutorId,
            'amount' => $amount,
            'sessions_count' => $sessionsCount,
            'period_start' => $request->period_start,
            'period_end' => $request->period_end,
            'status' => 'pending',
        ]);

        return redirect()->route('admin.payouts.index')->with('success', "Payout of RM {$amount} created for {$sessionsCount} session(s).");
    }

    public function show(TutorPayout $payout)
    {
        $payout->load('tutor');

        $sessions = Payment::where('status', 'success')
            ->whereHas('booking', fn ($q) => $q->where('tutor_id', $payout->tutor_id))
            ->whereHas('session', fn ($q) => $q->whereBetween('session_date', [$payout->period_start, $payout->period_end]))
            ->with(['booking.student', 'booking.subject', 'session'])
            ->get();

        return Inertia::render('Admin/Payouts/Show', [
            'payout' => $payout,
            'sessions' => $sessions,
        ]);
    }

    public function markProcessing(TutorPayout $payout)
    {
        abort_unless($payout->status === 'pending', 403);
        $payout->update(['status' => 'processing']);

        return back()->with('success', 'Payout marked as processing.');
    }

    public function markPaid(Request $request, TutorPayout $payout)
    {
        abort_unless(in_array($payout->status, ['pending', 'processing']), 403);

        $request->validate(['reference' => 'nullable|string|max:255']);

        $payout->update([
            'status' => 'paid',
            'paid_at' => now(),
            'reference' => $request->reference,
        ]);

        return back()->with('success', 'Payout marked as paid.');
    }
}
