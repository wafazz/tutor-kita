<?php

use App\Enums\DeliveryMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Record how a lesson is delivered, as one of five modes.
     *
     * The old location_type could not say who travels: 'home' covered both the
     * tutor going to the student and the student coming to the tutor, and
     * 'center' had nothing behind it. Both old columns are left in place so
     * existing screens keep working while the modes are rolled through.
     */
    public function up(): void
    {
        Schema::table('tutor_requests', function (Blueprint $table) {
            $table->string('delivery_mode', 32)->nullable()->after('preferred_location');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('delivery_mode', 32)->nullable()->after('location_type');
        });

        // Existing rows: 'online' becomes one-to-one online, everything else
        // becomes the tutor travelling — the only behaviour the old data can
        // have meant.
        foreach ([['tutor_requests', 'preferred_location'], ['bookings', 'location_type']] as [$table, $legacy]) {
            DB::table($table)->where($legacy, 'online')
                ->update(['delivery_mode' => DeliveryMode::OnlineSolo->value]);

            DB::table($table)->whereNull('delivery_mode')
                ->update(['delivery_mode' => DeliveryMode::HomeStudent->value]);
        }
    }

    public function down(): void
    {
        Schema::table('tutor_requests', fn (Blueprint $t) => $t->dropColumn('delivery_mode'));
        Schema::table('bookings', fn (Blueprint $t) => $t->dropColumn('delivery_mode'));
    }
};
