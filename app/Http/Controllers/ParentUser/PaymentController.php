<?php

namespace App\Http\Controllers\ParentUser;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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

    public function pay(Payment $payment)
    {
        abort_unless($payment->parent_id === auth()->id(), 403);
        abort_unless($payment->status === 'pending', 403);

        $pat = Setting::get('bayarcash_api_key');
        $secretKey = Setting::get('bayarcash_secret_key');
        $portalKey = Setting::get('bayarcash_portal_key');
        $isSandbox = Setting::get('bayarcash_sandbox', '1');

        // If API keys not configured, fallback to manual
        if (!$pat || !$portalKey || !$secretKey) {
            $payment->update([
                'status' => 'success',
                'payment_method' => 'manual',
                'paid_at' => now(),
            ]);
            $this->createBookingFromPayment($payment);
            return redirect()->route('parent.requests.show', $payment->tutor_request_id)
                ->with('success', 'Payment completed (manual mode).');
        }

        $baseUrl = $isSandbox === '1'
            ? 'https://console.bayarcash-sandbox.com/api/v2'
            : 'https://console.bayar.cash/api/v2';

        $orderNo = 'TH-' . $payment->id . '-' . Str::random(6);
        $payment->update(['transaction_id' => $orderNo]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $amount = number_format((float) $payment->amount, 2, '.', '');

        // Build payload for checksum (sorted by key)
        $checksumData = [
            'amount' => $amount,
            'order_number' => $orderNo,
            'payer_email' => $user->email,
            'payer_name' => $user->name,
            'payment_channel' => 1,
        ];
        ksort($checksumData);
        $checksumString = implode('|', $checksumData);
        $checksum = hash_hmac('sha256', $checksumString, $secretKey);

        $response = Http::withToken($pat)->post("{$baseUrl}/payment-intents", [
            'payment_channel' => 1,
            'portal_key' => $portalKey,
            'order_number' => $orderNo,
            'amount' => $amount,
            'payer_name' => $user->name,
            'payer_email' => $user->email,
            'payer_telephone_number' => $user->phone ?? '',
            'return_url' => route('parent.payments.callback'),
            'checksum' => $checksum,
        ]);

        if ($response->successful() && isset($response->json()['url'])) {
            return Inertia::location($response->json()['url']);
        }

        // API call failed — log error and show message
        \Log::error('Bayarcash API error', [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ]);

        return redirect()->route('parent.requests.show', $payment->tutor_request_id)
            ->with('error', 'Payment gateway error. Please try again or contact admin.');
    }

    public function callback(Request $request)
    {
        $orderNo = $request->input('order_number') ?? $request->input('record_number');
        $status = $request->input('status_id') ?? $request->input('status');
        $transactionId = $request->input('transaction_id') ?? $request->input('exchange_reference_number');

        $payment = Payment::where('transaction_id', $orderNo)->first();

        if (!$payment) {
            return redirect()->route('parent.payments.index')->with('error', 'Payment not found.');
        }

        // Bayarcash status: 1 = new, 2 = pending, 3 = successful, 4 = failed, 5 = cancelled
        if ($status == 3 || $status === 'success') {
            $payment->update([
                'status' => 'success',
                'payment_method' => 'fpx',
                'gateway' => 'bayarcash',
                'transaction_id' => $transactionId ?? $orderNo,
                'paid_at' => now(),
            ]);

            $this->createBookingFromPayment($payment);

            return redirect()->route('parent.requests.show', $payment->tutor_request_id)
                ->with('success', 'Payment successful! Your booking has been created.');
        }

        $payment->update(['status' => 'failed']);

        return redirect()->route('parent.requests.show', $payment->tutor_request_id)
            ->with('error', 'Payment failed. Please try again.');
    }

    private function createBookingFromPayment(Payment $payment): void
    {
        if ($payment->booking_id) {
            return;
        }

        $tutorRequest = $payment->tutorRequest;
        if (!$tutorRequest) {
            return;
        }

        // Get all requests to create bookings for (grouped or single)
        $requests = $tutorRequest->request_group
            ? \App\Models\TutorRequest::where('request_group', $tutorRequest->request_group)
                ->where('status', 'matched')
                ->with(['matchedTutor.tutorProfile', 'package'])
                ->get()
            : collect([$tutorRequest->load(['matchedTutor.tutorProfile', 'package'])]);

        $firstBooking = null;

        foreach ($requests as $req) {
            $tutor = $req->matchedTutor;
            if (!$tutor) {
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
                'location_type' => $req->location_type,
                'location_address' => $req->location_address,
                'hourly_rate' => $tutorProfile?->hourly_rate ?? 0,
                'commission_rate' => $tutorProfile?->commission_rate ?? 20,
                'status' => 'confirmed',
            ]);

            if (!$firstBooking) {
                $firstBooking = $booking;
            }

            $req->update(['status' => 'closed']);
        }

        if ($firstBooking) {
            $payment->update(['booking_id' => $firstBooking->id]);
        }
    }
}
