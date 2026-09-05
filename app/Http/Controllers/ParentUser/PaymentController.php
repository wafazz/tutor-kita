<?php

namespace App\Http\Controllers\ParentUser;

use App\Enums\DeliveryMode;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\TutorRequest;
use App\Support\Payments\Billplz;
use App\Support\SessionScheduler;
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
     * Finish a successful payment, whichever kind it is.
     *
     * A seat in a group class already has its booking — it was created when
     * the student enrolled — so building one from a tutor request would be
     * wrong, and there is no request to redirect back to either.
     */
    private function completePayment(Payment $payment)
    {
        $enrolment = $payment->enrolment;

        if ($enrolment) {
            $enrolment->update(['status' => 'active']);

            return redirect()->route('parent.classes.show', $enrolment->class_session_id)
                ->with('success', 'Payment successful — the seat is confirmed.');
        }

        $this->createBookingFromPayment($payment);

        return redirect()->route('parent.requests.show', $payment->tutor_request_id)
            ->with('success', 'Payment successful! Your booking has been created.');
    }

    /**
     * The legacy location_type a delivery mode corresponds to.
     */
    private function locationTypeFor(TutorRequest $request): string
    {
        $mode = $request->deliveryMode();

        return match (true) {
            $mode->isOnline() => 'online',
            $mode === DeliveryMode::CentreGroup => 'center',
            default => 'home',
        };
    }

    private function createBookingFromPayment(Payment $payment): void
    {
        if ($payment->booking_id) {
            return;
        }

        $tutorRequest = $payment->tutorRequest;
        if (! $tutorRequest) {
            return;
        }

        // Get all requests to create bookings for (grouped or single)
        $requests = $tutorRequest->request_group
            ? TutorRequest::where('request_group', $tutorRequest->request_group)
                ->where('status', 'matched')
                ->with(['matchedTutor.tutorProfile', 'package'])
                ->get()
            : collect([$tutorRequest->load(['matchedTutor.tutorProfile', 'package'])]);

        $firstBooking = null;

        foreach ($requests as $req) {
            $tutor = $req->matchedTutor;
            if (! $tutor) {
                continue;
            }

            $tutorProfile = $tutor->tutorProfile;
            $package = $req->package;

            $booking = Booking::create([
                'tutor_request_id' => $req->id,
                'tutor_id' => $tutor->id,
                'parent_id' => $req->parent_id,
                'student_id' => $req->student_id,
                'subject_id' => $req->subject_id,
                'schedule_day' => $req->schedule_day,
                'schedule_time' => $req->schedule_time,
                'duration_hours' => $req->duration_hours ?? $package?->duration_hours ?? 1,
                // bookings.location_type is NOT NULL but the request's is not:
                // it is only filled when a tutor accepts through the job flow,
                // so an admin-matched request would take the parent's money and
                // then fail to create anything. Derived from the delivery mode
                // when the request never said.
                'location_type' => $req->location_type ?? $this->locationTypeFor($req),
                'location_address' => $req->location_address,
                'hourly_rate' => $tutorProfile?->hourly_rate ?? 0,
                'commission_rate' => $tutorProfile?->commission_rate ?? Setting::defaultCommissionRate(),
                'status' => 'confirmed',
            ]);

            if (! $firstBooking) {
                $firstBooking = $booking;
            }

            // Sessions are what delivery is recorded against, so a booking
            // without them can never accrue under a per-session package. They
            // are laid out now rather than left to be generated by hand.
            app(SessionScheduler::class)->ensure(
                $booking,
                max(1, (int) ($package?->total_sessions ?? 1)),
                durationHours: (float) $booking->duration_hours,
            );

            $req->update(['status' => 'closed']);
        }

        if ($firstBooking) {
            $payment->update(['booking_id' => $firstBooking->id]);

            // A grouped request spans several tutors under one payment; each
            // tutor is paid from their own booking, so split the recorded
            // totals across them now that the bookings exist.
            $payment->allocateToBookings();
        }
    }
}
