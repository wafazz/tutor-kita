<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When a tutor becomes payable for a booking, chosen per package.
     *
     *  upfront       — payable as soon as the parent's payment succeeds
     *  per_session   — accrues 1/total_sessions per completed session
     *  on_completion — payable only once every session is delivered
     *
     * Existing packages default to per_session: it pays for work actually
     * delivered, and unlike on_completion it does not strand a tutor's money
     * for the whole run of a package already in progress.
     */
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->enum('payout_policy', ['upfront', 'per_session', 'on_completion'])
                ->default('per_session')
                ->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('payout_policy');
        });
    }
};
