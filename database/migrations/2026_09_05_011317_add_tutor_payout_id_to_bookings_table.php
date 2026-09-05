<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which payout has already committed to paying this booking.
     *
     * Payout runs select by session date, but the payout amount is held per
     * booking — so a booking whose sessions straddle two periods was counted
     * in full by both. Claiming the booking makes it payable exactly once,
     * however the periods are drawn.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('tutor_payout_id')->nullable()->after('payment_id')
                ->constrained('tutor_payouts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tutor_payout_id');
        });
    }
};
