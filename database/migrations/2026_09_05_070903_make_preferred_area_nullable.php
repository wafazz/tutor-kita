<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An online lesson has no area, so the column must allow none.
     *
     * Validation only required preferred_area for lessons at a home, but the
     * column did not allow null — so every online request passed validation
     * and then failed on insert. The lesson genuinely has no area; the schema
     * was the thing that was wrong.
     */
    public function up(): void
    {
        Schema::table('tutor_requests', function (Blueprint $table) {
            $table->string('preferred_area')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tutor_requests', function (Blueprint $table) {
            $table->string('preferred_area')->nullable(false)->change();
        });
    }
};
