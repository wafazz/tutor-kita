<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tutor_requests', function (Blueprint $table) {
            $table->boolean('tutor_accepted')->default(false)->after('matched_at');
            $table->string('schedule_day')->nullable()->after('tutor_accepted');
            $table->string('schedule_time')->nullable()->after('schedule_day');
            $table->decimal('duration_hours', 4, 1)->nullable()->after('schedule_time');
            $table->string('location_type')->nullable()->after('duration_hours');
            $table->string('location_address', 500)->nullable()->after('location_type');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_requests', function (Blueprint $table) {
            $table->dropColumn(['tutor_accepted', 'schedule_day', 'schedule_time', 'duration_hours', 'location_type', 'location_address']);
        });
    }
};
