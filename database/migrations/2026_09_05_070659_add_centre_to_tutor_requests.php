<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which centre a centre-based request is for.
     *
     * Distance for a centre group has to be measured from the centre the
     * lesson actually happens at. Without this there was nothing to measure
     * from, and matching fell back to whichever active centre happened to come
     * first — a real distance to the wrong place, which is worse than none.
     */
    public function up(): void
    {
        Schema::table('tutor_requests', function (Blueprint $table) {
            $table->foreignId('centre_id')->nullable()->after('package_id')
                ->constrained('centres')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tutor_requests', fn (Blueprint $table) => $table->dropConstrainedForeignId('centre_id'));
    }
};
