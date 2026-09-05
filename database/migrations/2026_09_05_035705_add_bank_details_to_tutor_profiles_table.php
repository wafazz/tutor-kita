<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where a tutor's payout is actually sent.
     *
     * Payouts were recordable but not executable — nothing held a destination
     * account, so an admin marking a payout paid had no in-system record of
     * where the money went.
     *
     * The account number is stored encrypted (see TutorProfile casts), so the
     * column is text rather than a short string: ciphertext is far longer than
     * the ~20 characters an account number needs.
     */
    public function up(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('commission_rate');
            $table->text('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_account_name')->nullable()->after('bank_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_number', 'bank_account_name']);
        });
    }
};
