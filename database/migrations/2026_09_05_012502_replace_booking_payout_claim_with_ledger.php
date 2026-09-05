<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Under per_session accrual a booking is paid across several payout runs,
     * so a single tutor_payout_id can no longer express what has been paid.
     *
     * Replaced by a running paid_out_amount on the booking (the guard against
     * paying the same money twice) plus a pivot recording which payout paid
     * which slice (the audit trail).
     */
    public function up(): void
    {
        Schema::create('booking_tutor_payout', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('tutor_payout_id')->constrained('tutor_payouts')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->unique(['booking_id', 'tutor_payout_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('paid_out_amount', 10, 2)->default(0)->after('tutor_payout');
        });

        // Carry existing whole-booking claims into the new shape.
        $claimed = DB::table('bookings')
            ->whereNotNull('tutor_payout_id')
            ->get(['id', 'tutor_payout_id', 'tutor_payout']);

        foreach ($claimed as $booking) {
            DB::table('booking_tutor_payout')->insert([
                'booking_id' => $booking->id,
                'tutor_payout_id' => $booking->tutor_payout_id,
                'amount' => $booking->tutor_payout,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('bookings')->where('id', $booking->id)
                ->update(['paid_out_amount' => $booking->tutor_payout]);
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tutor_payout_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('tutor_payout_id')->nullable()->after('payment_id')
                ->constrained('tutor_payouts')->nullOnDelete();
        });

        foreach (DB::table('booking_tutor_payout')->get() as $row) {
            DB::table('bookings')->where('id', $row->booking_id)
                ->update(['tutor_payout_id' => $row->tutor_payout_id]);
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('paid_out_amount');
        });

        Schema::dropIfExists('booking_tutor_payout');
    }
};
