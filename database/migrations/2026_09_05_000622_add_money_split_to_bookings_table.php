<?php

use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Money is settled per tutor, but a grouped request raises a single payment
     * covering several tutors. Holding the split on the booking — the only
     * per-tutor row in the chain — lets payouts attribute each tutor's share.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->default(0)->after('commission_rate');
            $table->decimal('commission_amount', 10, 2)->default(0)->after('amount');
            $table->decimal('tutor_payout', 10, 2)->default(0)->after('commission_amount');

            // Which payment settled this booking. A grouped request links every
            // booking in the group to the one payment that covers them all,
            // where payments.booking_id can only ever point back at the first.
            $table->foreignId('payment_id')->nullable()->after('tutor_payout')
                ->constrained('payments')->nullOnDelete();
        });

        // Backfill by distributing each payment's recorded totals, never by
        // recomputing from current rates — subject prices and packages drift,
        // and the charged amount is the one the parent actually paid.
        Payment::with('tutorRequest')->chunkById(100, function ($payments) {
            foreach ($payments as $payment) {
                $payment->allocateToBookings();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_id');
            $table->dropColumn(['amount', 'commission_amount', 'tutor_payout']);
        });
    }
};
