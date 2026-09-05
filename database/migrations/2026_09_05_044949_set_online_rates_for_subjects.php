<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Give every subject a real online rate: 80% of its home rate, rounded to
     * the nearest RM5.
     *
     * Every subject shipped with hourly_rate_online = 0, which priced any
     * online booking at RM0 — the parent charged nothing, the tutor earning
     * nothing. The pricing code now falls back to the home rate rather than
     * charging zero, but that is a safety net, not a price. This sets the
     * intended rate so the fallback stops applying.
     *
     * Only rows still sitting at 0 are touched, so a rate set deliberately —
     * before or after this runs — is never overwritten.
     */
    public function up(): void
    {
        $subjects = DB::table('subjects')
            ->where('hourly_rate_online', 0)
            ->get(['id', 'hourly_rate_home']);

        foreach ($subjects as $subject) {
            $online = round(((float) $subject->hourly_rate_home * 0.8) / 5) * 5;

            DB::table('subjects')->where('id', $subject->id)
                ->update(['hourly_rate_online' => $online]);
        }
    }

    /**
     * Irreversible by design: rolling back would restore a zero rate, which is
     * the defect this exists to remove.
     */
    public function down(): void {}
};
