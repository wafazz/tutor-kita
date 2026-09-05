<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hold the Malaysian postcode directory as what it actually is.
     *
     * The old table was built for postcode centroids and required coordinates.
     * The directory has none — it maps a postcode to a city and state — so
     * requiring them would make it unimportable. Coordinates remain as nullable
     * columns to be filled from another source later; until they are, the
     * postcode geocoder resolves nothing rather than placing anyone wrongly.
     *
     * Replaced rather than renamed: index names keep the old table's prefix
     * after a rename, and the table was empty, so there is nothing to preserve.
     */
    public function up(): void
    {
        Schema::dropIfExists('postcode_centroids');

        Schema::create('postcodes', function (Blueprint $table) {
            $table->id();
            $table->string('postcode', 10);
            $table->string('city');
            $table->string('state');

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->timestamps();

            // A postcode is not unique on its own: 40160 covers both Shah Alam
            // and Sungai Buloh.
            $table->unique(['postcode', 'city']);
            $table->index('postcode');
            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postcodes');

        Schema::create('postcode_centroids', function (Blueprint $table) {
            $table->id();
            $table->string('postcode', 10)->unique();
            $table->string('area')->nullable();
            $table->string('state')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamps();
        });
    }
};
