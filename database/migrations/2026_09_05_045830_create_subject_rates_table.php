<?php

use App\Enums\DeliveryMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One rate per subject per delivery mode.
     *
     * Two columns on subjects could only ever express two modes. A row per mode
     * means a new delivery mode needs a rate row, not a schema change — and
     * group modes can carry a per-student rate and a capacity that solo modes
     * have no use for.
     */
    public function up(): void
    {
        Schema::create('subject_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('delivery_mode', 32);
            $table->decimal('hourly_rate', 8, 2);

            // Group modes only: how many students may share one session.
            $table->unsignedSmallInteger('max_students')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['subject_id', 'delivery_mode']);
        });

        // Carry the existing two-column rates into the new shape so nothing
        // reprices. Group modes are left unset on purpose: a per-student group
        // price is a business decision, and approval already refuses to raise a
        // zero-amount payment, so an unpriced mode fails loudly rather than
        // billing nothing.
        $solo = [DeliveryMode::HomeStudent, DeliveryMode::HomeTutor, DeliveryMode::OnlineSolo];

        foreach (DB::table('subjects')->get(['id', 'hourly_rate_home', 'hourly_rate_online']) as $subject) {
            foreach ($solo as $mode) {
                $rate = (float) $subject->{$mode->legacyRateColumn()};

                if ($rate <= 0) {
                    continue;
                }

                DB::table('subject_rates')->insert([
                    'subject_id' => $subject->id,
                    'delivery_mode' => $mode->value,
                    'hourly_rate' => $rate,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_rates');
    }
};
