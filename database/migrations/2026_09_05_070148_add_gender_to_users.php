<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tutor gender, so a parent's stated preference can actually be honoured.
     *
     * tutor_requests.preferred_tutor_gender already existed, but there was
     * nothing to compare it against, so the preference was collected and then
     * silently ignored. Nullable, because it is not required and an unknown
     * gender must read as unknown rather than as a mismatch.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('gender', ['male', 'female'])->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('gender'));
    }
};
