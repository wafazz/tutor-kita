<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Postcode to approximate coordinates, for the free geocoding driver.
     *
     * Left empty here: the table is the mechanism, the data is a separate
     * import. The postcode driver simply resolves nothing until it is filled,
     * which leaves records unplaced rather than wrongly placed.
     */
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('postcode_centroids');
    }
};
