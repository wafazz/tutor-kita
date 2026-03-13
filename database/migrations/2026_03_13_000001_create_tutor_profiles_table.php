<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('ic_number')->nullable();
            $table->json('subjects');
            $table->string('education_level')->nullable();
            $table->integer('experience_years')->default(0);
            $table->text('bio')->nullable();
            $table->decimal('hourly_rate', 8, 2);
            $table->string('location_area');
            $table->string('location_state');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('availability')->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->json('documents')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->integer('total_sessions')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_profiles');
    }
};
