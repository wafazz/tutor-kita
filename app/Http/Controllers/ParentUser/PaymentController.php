<?php

namespace App\Http\Controllers\ParentUser;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Setting;
use App\Support\Payments\Billplz;
use App\Support\Payments\PaymentCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::where('parent_id', auth()->id())
            ->with(['booking.tutor', 'booking.student', 'booking.subject', 'tutorRequest.subject', 'session']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);

        $totals = [
            'paid' => Payment::where('parent_id', auth()->id())->where('status', 'success')->sum('amount'),
            'pending' => Payment::where('parent_id', auth()->id())->where('status', 'pending')->sum('amount'),
        ];

        return Inertia::render('Parent/Payments/Index', [
            'payments' => $payments,
            'totals' => $totals,
            'filters' => $request->only('status'),
        ]);
    }

    public function show(Payment $payment)
    {
        abort_unless($payment->parent_id === auth()->id(), 403);

        $payment->load(['booking.tutor', 'booking.student', 'booking.subject', 'tutorRequest.subject', 'session']);

        return Inertia::render('Parent/Payments/Show', [
            'payment' => $payment,
        ]);
    }

    public function pay(Payment $payment, Billplz $billplz)
    {
        abort_unless($payment->parent_id === auth()->id(), 403);
        abort_unless($payment->status === 'pending', 403);

        // Settling without collecting anything is a development convenience and
        // has to be asked for explicitly. Keying it off missing gateway keys
        // would mean a mistyped key in production gives everything away free.
        if (Setting::get('payments_manual_mode', '0') === '1') {
            $payment->update([
                'status' => 'success',
                'payment_method' => 'manual',
                'paid_at' => now(),
            ]);

            return $this->completePayment($payment)
                ->with('success', 'Payment recorded (manual mode — no money was collected).');
        }

        if (! $billplz->configured()) {
            return back()->with('error', 'No payment gateway is configured. Ask an administrator to set it up.');
        }

        $user = auth()->user();

        $bill = $billplz->createBill([
            'email' => $user->email,
            'name' => $user->name,
            'amount' => (float) $payment->amount,
            'description' => 'TutorHUB payment #'.$payment->id,
            'reference' => 'TH-'.$payment->id,
            'callbackUrl' => route('payments.billplz.webhook'),
            'redirectUrl' => route('parent.payments.return'),
        ]);

        if (! $bill) {
            return back()->with('error', 'The payment gateway could not be reached. Please try again shortly.');
        }

        $payment->update(['gateway' => 'billplz', 'transaction_id' => $bill['id']]);

        return Inertia::location($bill['url']);
    }

    /**
     * Server-to-server notification from Billplz. This is what settles a payment.
     *
     * Unauthenticated by necessity — Billplz calls it, not the payer — so the
     * signature is the only thing establishing that the message is genuine.
     */
    public function webhook(Request $request, Billplz $billplz)
    {
        $payload = $request->all();

        if (! $billplz->webhookSignatureIsValid($payload)) {
            Log::warning('Rejected a Billplz webhook with an invalid signature', [
                'bill' => $payload['id'] ?? null,
            ]);

            return response('invalid signature', 403);
        }

        $payment = Payment::where('transaction_id', $payload['id'] ?? '')->first();

        if (! $payment) {
            // Acknowledged so Billplz stops retrying something we cannot match.
            return response('unknown bill', 200);
        }

        // Billplz sends "true"/"false" as strings.
        $paid = filter_var($payload['paid'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $paid) {
            if ($payment->status === 'pending') {
                $payment->update(['status' => 'failed']);
            }

            return response('ok', 200);
        }

        // The amount is confirmed against what was owed: a bill settled for
        // less than the invoice must not pass as payment in full.
        $paidAmount = ((int) ($payload['paid_amount'] ?? 0)) / 100;

        if (round($paidAmount, 2) + 0.001 < round((float) $payment->amount, 2)) {
            Log::warning('Billplz reported an underpayment', [
                'payment' => $payment->id, 'owed' => $payment->amount, 'paid' => $paidAmount,
            ]);

            return response('amount mismatch', 200);
        }

        // Webhooks are retried, so settling twice must be harmless.
        if ($payment->status !== 'success') {
            $payment->update([
                'status' => 'success',
                'payment_method' => 'fpx',
                'gateway' => 'billplz',
                'paid_at' => now(),
            ]);

            $this->completePayment($payment);
        }

        return response('ok', 200);
    }

    /**
     * Where the payer lands after Billplz.
     *
     * Shows an outcome and nothing more. The redirect is a URL the payer
     * controls, so it never settles anything — if the webhook has not arrived
     * yet, Billplz is asked directly rather than the browser being believed.
     */
    public function paymentReturn(Request $request, Billplz $billplz)
    {
        $params = $request->input('billplz', []);
        $billId = $params['id'] ?? null;

        $payment = $billId ? Payment::where('transaction_id', $billId)->first() : null;

        if (! $payment || $payment->parent_id !== auth()->id()) {
            return redirect()->route('parent.payments.index');
        }

        if ($payment->status === 'success') {
            return $this->completedRedirect($payment);
        }

        // The webhook may simply not have landed yet. A signed redirect is a
        // reason to ask Billplz, never a reason to believe the browser.
        if ($billplz->redirectSignatureIsValid($params)) {
            $bill = $billplz->fetchBill($billId);

            if ($bill && filter_var($bill['paid'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $payment->update([
                    'status' => 'success',
                    'payment_method' => 'fpx',
                    'gateway' => 'billplz',
                    'paid_at' => now(),
                ]);

                return $this->completePayment($payment);
            }
        }

        return redirect()->route('parent.payments.show', $payment->id)
            ->with('info', 'We are confirming your payment with the bank. This page will show it as paid once confirmed.');
    }

    /** Where to send someone whose payment is already settled. */
    private function completedRedirect(Payment $payment)
    {
        $enrolment = $payment->enrolment;

        return $enrolment
            ? redirect()->route('parent.classes.show', $enrolment->class_session_id)
                ->with('success', 'Payment confirmed.')
            : redirect()->route('parent.payments.show', $payment->id)
                ->with('success', 'Payment confirmed.');
    }

    /**
     * Complete the transaction, then send the payer somewhere sensible.
     *
     * The work itself lives in PaymentCompletion so that every route to a
     * successful payment — including an admin marking one paid — does exactly
     * the same thing.
     */
    private function completePayment(Payment $payment)
    {
        app(PaymentCompletion::class)->complete($payment);

        return $this->completedRedirect($payment->fresh());
    }
}
