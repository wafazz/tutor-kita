<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A place students travel to for a group class.
     *
     * owner_user_id decides who runs it without committing to one model:
     * null is a platform centre managed by HQ, and a tutor id is that tutor's
     * own venue. Choosing "platform only" or "both" later is a question of
     * which rows exist, not a schema change.
     */
    public function up(): void
    {
        Schema::create('centres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('address', 500);
            $table->string('area')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode', 10)->nullable();

            // Nullable until geocoded; a centre without coordinates simply does
            // not appear in radius results rather than matching everything.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('geocoded_at')->nullable();

            $table->unsignedSmallInteger('capacity')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centres');
    }
};
