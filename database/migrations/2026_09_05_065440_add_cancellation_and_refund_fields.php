<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Record what a cancellation decided.
     *
     * Cancelling after payment is a transaction across several records, and
     * the outcome has to be written down: how much is owed back, when it was
     * agreed, and why the booking ended. Without that, a cancelled booking is
     * indistinguishable from one that simply stopped.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('tutor_payout');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('cancellation_reason', 500)->nullable()->after('notes');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $t) => $t->dropColumn(['refunded_amount', 'refunded_at']));
        Schema::table('bookings', fn (Blueprint $t) => $t->dropColumn(['cancellation_reason', 'cancelled_at']));
    }
};
