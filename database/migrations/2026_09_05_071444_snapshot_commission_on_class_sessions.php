<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix the commission a class was created under.
     *
     * A class read the tutor's current commission every time its shares were
     * recalculated, so renegotiating a tutor's rate silently repriced classes
     * that had already been sold: a student who enrolled at 20% saw the tutor's
     * share drop when the rate later moved to 50%. Commercial terms agreed at
     * the time have to stay agreed.
     *
     * Existing classes are backfilled from their tutor's present rate, which is
     * the only rate that was ever applied to them.
     */
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->nullable()->after('price_per_student');
        });

        // Done row by row rather than with an UPDATE ... JOIN, which is MySQL
        // syntax and fails on the SQLite the tests run against.
        $rates = DB::table('tutor_profiles')->pluck('commission_rate', 'user_id');

        foreach (DB::table('class_sessions')->whereNull('commission_rate')->get(['id', 'tutor_id']) as $class) {
            DB::table('class_sessions')->where('id', $class->id)
                ->update(['commission_rate' => $rates[$class->tutor_id] ?? 20]);
        }
    }

    public function down(): void
    {
        Schema::table('class_sessions', fn (Blueprint $table) => $table->dropColumn('commission_rate'));
    }
};
