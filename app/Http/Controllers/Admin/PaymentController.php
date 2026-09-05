<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\Payments\PaymentCompletion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        // A payment raised at approval has no booking until it succeeds, so
        // fall back to the originating request for who and what it is for.
        $query = Payment::with([
            'booking.tutor', 'booking.student', 'booking.subject', 'parent', 'session',
            'tutorRequest.matchedTutor:id,name', 'tutorRequest.subject:id,name', 'tutorRequest.student:id,name',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);

        $totals = [
            'total' => Payment::where('status', 'success')->sum('amount'),
            'commission' => Payment::where('status', 'success')->sum('commission_amount'),
            'tutorPayout' => Payment::where('status', 'success')->sum('tutor_payout'),
            'pending' => Payment::where('status', 'pending')->sum('amount'),
        ];

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'totals' => $totals,
            'filters' => $request->only('status'),
        ]);
    }

    public function show(Payment $payment)
    {
        $payment->load([
            'booking.tutor', 'booking.student', 'booking.subject', 'parent', 'session',
            'tutorRequest.matchedTutor:id,name', 'tutorRequest.subject:id,name', 'tutorRequest.student:id,name',
        ]);

        return Inertia::render('Admin/Payments/Show', [
            'payment' => $payment,
        ]);
    }

    public function markPaid(Payment $payment, PaymentCompletion $completion)
    {
        abort_unless($payment->status === 'pending', 403);

        $payment->update([
            'status' => 'success',
            'payment_method' => 'manual',
            'paid_at' => now(),
        ]);

        // Marking a payment paid is not the same as completing what it pays
        // for. Without this the booking and its sessions are never created,
        // and the tutor's share becomes money that no payout can reach.
        $completion->complete($payment);

        return back()->with('success', 'Payment marked as paid, and the booking created.');
    }

    public function markFailed(Payment $payment)
    {
        abort_unless($payment->status === 'pending', 403);

        $payment->update(['status' => 'failed']);

        return back()->with('success', 'Payment marked as failed.');
    }
}
