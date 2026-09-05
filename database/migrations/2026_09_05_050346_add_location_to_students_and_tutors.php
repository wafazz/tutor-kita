<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Addresses and coordinates for the parties radius matching measures.
     *
     * Students had no address at all, so "tutor travels to the student" had no
     * destination. Tutors already had latitude and longitude columns, but no
     * address to derive them from and no radius saying how far they will go.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('address', 500)->nullable()->after('education_level');
            $table->string('area')->nullable()->after('address');
            $table->string('state')->nullable()->after('area');
            $table->string('postcode', 10)->nullable()->after('state');
            $table->decimal('latitude', 10, 7)->nullable()->after('postcode');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->timestamp('geocoded_at')->nullable()->after('longitude');

            $table->index(['latitude', 'longitude']);
        });

        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->string('address', 500)->nullable()->after('location_state');
            $table->string('postcode', 10)->nullable()->after('address');
            $table->timestamp('geocoded_at')->nullable()->after('longitude');

            // How far this tutor is willing to travel for home_student work.
            // Null means unset rather than zero, so an unanswered profile does
            // not read as "will not travel at all".
            $table->unsignedSmallInteger('travel_radius_km')->nullable()->after('geocoded_at');

            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropColumn(['address', 'area', 'state', 'postcode', 'latitude', 'longitude', 'geocoded_at']);
        });

        Schema::table('tutor_profiles', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropColumn(['address', 'postcode', 'geocoded_at', 'travel_radius_km']);
        });
    }
};
