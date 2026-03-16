<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_requests', function (Blueprint $table) {
            $table->string('preferred_location')->nullable()->after('preferred_area');
            $table->string('preferred_time')->nullable()->after('preferred_schedule');
            $table->string('preferred_tutor_gender')->nullable()->after('preferred_time');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_requests', function (Blueprint $table) {
            $table->dropColumn(['preferred_location', 'preferred_time', 'preferred_tutor_gender']);
        });
    }
};
