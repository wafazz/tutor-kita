<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['booking.tutor', 'booking.student', 'booking.subject', 'parent', 'session']);

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
        $payment->load(['booking.tutor', 'booking.student', 'booking.subject', 'parent', 'session']);

        return Inertia::render('Admin/Payments/Show', [
            'payment' => $payment,
        ]);
    }

    public function markPaid(Payment $payment)
    {
        abort_unless($payment->status === 'pending', 403);

        $payment->update([
            'status' => 'success',
            'payment_method' => 'manual',
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Payment marked as paid.');
    }

    public function markFailed(Payment $payment)
    {
        abort_unless($payment->status === 'pending', 403);

        $payment->update(['status' => 'failed']);

        return back()->with('success', 'Payment marked as failed.');
    }
}
