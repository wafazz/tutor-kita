<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TutorProfile;
use App\Models\TutorRequest;
use App\Models\User;
use App\Support\Payments\Billplz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Billplz reports a payment twice: a server-to-server webhook, and a redirect
 * the payer's browser follows. Only the webhook is trustworthy — the redirect
 * is a URL the payer controls, so they choose whether, when and with what
 * parameters to visit it.
 *
 * The previous gateway settled payments straight from that redirect, with no
 * signature check and no owner check, so a parent could mark their own invoice
 * paid by visiting a URL. These pin that this cannot happen.
 */
class BillplzPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNING_KEY = 'test-x-signature-key';

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('billplz_api_key', 'key');
        Setting::set('billplz_collection_id', 'coll');
        Setting::set('billplz_x_signature_key', self::SIGNING_KEY);
        Setting::set('payments_manual_mode', '0');

        $this->parent = User::factory()->parent()->create();
    }

    private function pendingPayment(string $billId = 'bill_abc'): Payment
    {
        $tutor = User::factory()->tutor()->create();
        TutorProfile::create([
            'user_id' => $tutor->id, 'subjects' => [], 'hourly_rate' => 50,
            'location_area' => 'PJ', 'location_state' => 'Sel',
            'verification_status' => 'verified', 'commission_rate' => 20,
        ]);
        $subject = Subject::create(['name' => 'S'.uniqid(), 'category' => 'academic',
            'hourly_rate_home' => 60, 'hourly_rate_online' => 50, 'is_active' => true]);
        $package = Package::create(['name' => 'P', 'package_type' => 'all', 'total_sessions' => 1,
            'duration_hours' => 2, 'price' => 0, 'is_active' => true, 'sort_order' => 1]);
        $student = Student::create(['parent_id' => $this->parent->id, 'name' => 'Kid', 'age' => 14]);

        $request = TutorRequest::create([
            'parent_id' => $this->parent->id, 'student_id' => $student->id, 'subject_id' => $subject->id,
            'package_id' => $package->id, 'preferred_area' => 'PJ', 'schedule_day' => 'monday',
            'schedule_time' => '10:00', 'duration_hours' => 2,
            'status' => 'matched', 'matched_tutor_id' => $tutor->id,
        ]);

        return Payment::create([
            'tutor_request_id' => $request->id, 'parent_id' => $this->parent->id,
            'amount' => 120, 'commission_amount' => 24, 'tutor_payout' => 96,
            'payment_method' => 'fpx', 'status' => 'pending', 'transaction_id' => $billId,
        ]);
    }

    /** Sign a webhook payload the way Billplz does. */
    private function sign(array $payload, string $prefix = ''): string
    {
        $pairs = [];
        foreach ($payload as $key => $value) {
            $pairs[] = $prefix.$key.($value ?? '');
        }
        sort($pairs, SORT_STRING);

        return hash_hmac('sha256', implode('|', $pairs), self::SIGNING_KEY);
    }

    // ---- the vulnerability that prompted this ----

    public function test_a_payer_cannot_settle_their_own_invoice_by_visiting_a_url(): void
    {
        $payment = $this->pendingPayment();

        // No signature: exactly the old attack, now against the new endpoint.
        $this->actingAs($this->parent)
            ->get('/parent/payments/return?billplz[id]=bill_abc&billplz[paid]=true');

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame(0, Booking::count());
    }

    public function test_a_forged_webhook_signature_is_refused(): void
    {
        $payment = $this->pendingPayment();

        $this->postJson('/payments/billplz/webhook', [
            'id' => 'bill_abc', 'paid' => 'true', 'paid_amount' => 12000,
            'x_signature' => 'not-the-real-signature',
        ])->assertStatus(403);

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_a_webhook_is_refused_when_no_signing_key_is_configured(): void
    {
        Setting::set('billplz_x_signature_key', '');
        $payment = $this->pendingPayment();

        // Unverifiable is not the same as trusted.
        $this->postJson('/payments/billplz/webhook', [
            'id' => 'bill_abc', 'paid' => 'true', 'paid_amount' => 12000, 'x_signature' => 'anything',
        ])->assertStatus(403);

        $this->assertSame('pending', $payment->fresh()->status);
    }

    // ---- the flow that should work ----

    public function test_a_correctly_signed_webhook_settles_the_payment(): void
    {
        $payment = $this->pendingPayment();

        $payload = ['id' => 'bill_abc', 'paid' => 'true', 'paid_amount' => 12000, 'state' => 'paid'];
        $payload['x_signature'] = $this->sign($payload);

        $this->postJson('/payments/billplz/webhook', $payload)->assertOk();

        $this->assertSame('success', $payment->fresh()->status);
        $this->assertSame(1, Booking::count());
    }

    public function test_settling_twice_does_not_duplicate_anything(): void
    {
        $payment = $this->pendingPayment();

        $payload = ['id' => 'bill_abc', 'paid' => 'true', 'paid_amount' => 12000];
        $payload['x_signature'] = $this->sign($payload);

        // Billplz retries its webhooks.
        $this->postJson('/payments/billplz/webhook', $payload)->assertOk();
        $this->postJson('/payments/billplz/webhook', $payload)->assertOk();

        $this->assertSame(1, Booking::count());
        $this->assertSame(1, Payment::where('status', 'success')->count());
    }

    public function test_an_underpayment_does_not_settle_the_invoice(): void
    {
        $payment = $this->pendingPayment();

        // RM1 against an RM120 invoice.
        $payload = ['id' => 'bill_abc', 'paid' => 'true', 'paid_amount' => 100];
        $payload['x_signature'] = $this->sign($payload);

        $this->postJson('/payments/billplz/webhook', $payload)->assertOk();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame(0, Booking::count());
    }

    public function test_an_unpaid_webhook_marks_the_payment_failed(): void
    {
        $payment = $this->pendingPayment();

        $payload = ['id' => 'bill_abc', 'paid' => 'false', 'paid_amount' => 0];
        $payload['x_signature'] = $this->sign($payload);

        $this->postJson('/payments/billplz/webhook', $payload)->assertOk();

        $this->assertSame('failed', $payment->fresh()->status);
    }

    public function test_a_webhook_for_an_unknown_bill_is_acknowledged_not_errored(): void
    {
        $payload = ['id' => 'bill_nobody', 'paid' => 'true', 'paid_amount' => 100];
        $payload['x_signature'] = $this->sign($payload);

        // Acknowledged so Billplz stops retrying something unmatched.
        $this->postJson('/payments/billplz/webhook', $payload)->assertOk();
    }

    // ---- manual mode ----

    public function test_manual_mode_is_off_unless_deliberately_enabled(): void
    {
        // Even with no gateway configured, nothing settles for free.
        Setting::set('billplz_api_key', '');
        Setting::set('billplz_collection_id', '');

        $payment = $this->pendingPayment();

        $this->actingAs($this->parent)->post("/parent/payments/{$payment->id}/pay")
            ->assertSessionHas('error');

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_manual_mode_settles_when_explicitly_switched_on(): void
    {
        Setting::set('payments_manual_mode', '1');
        $payment = $this->pendingPayment();

        $this->actingAs($this->parent)->post("/parent/payments/{$payment->id}/pay");

        $this->assertSame('success', $payment->fresh()->status);
        $this->assertSame('manual', $payment->fresh()->payment_method);
    }

    // ---- signature helper ----

    public function test_the_signature_check_accepts_only_the_exact_signature(): void
    {
        $billplz = app(Billplz::class);

        $payload = ['id' => 'x', 'paid' => 'true'];
        $valid = $payload + ['x_signature' => $this->sign($payload)];

        $this->assertTrue($billplz->webhookSignatureIsValid($valid));

        // One character of the payload changed invalidates it.
        $tampered = ['id' => 'y', 'paid' => 'true', 'x_signature' => $valid['x_signature']];
        $this->assertFalse($billplz->webhookSignatureIsValid($tampered));
    }

    // ---- coming back from the gateway ----

    public function test_the_return_page_does_not_depend_on_being_signed_in(): void
    {
        $payment = $this->pendingPayment();
        $payment->update(['status' => 'success', 'paid_at' => now()]);

        // The payer returns from another site, so their session may not have
        // survived. A completed payment must not greet them with a 403.
        $this->get('/payments/return?billplz[id]=bill_abc&billplz[paid]=true')
            ->assertRedirect(route('login'));
    }

    public function test_the_owner_is_taken_straight_to_their_payment(): void
    {
        $payment = $this->pendingPayment();
        $payment->update(['status' => 'success', 'paid_at' => now()]);

        $this->actingAs($this->parent)
            ->get('/payments/return?billplz[id]=bill_abc&billplz[paid]=true')
            ->assertRedirect(route('parent.payments.show', $payment->id));
    }

    public function test_the_return_page_alone_never_settles_a_payment(): void
    {
        $payment = $this->pendingPayment();

        // Unsigned, and claiming success.
        $this->actingAs($this->parent)
            ->get('/payments/return?billplz[id]=bill_abc&billplz[paid]=true');

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_an_unknown_bill_is_turned_away_quietly(): void
    {
        $this->get('/payments/return?billplz[id]=nobody')
            ->assertRedirect(route('login'));
    }
}
