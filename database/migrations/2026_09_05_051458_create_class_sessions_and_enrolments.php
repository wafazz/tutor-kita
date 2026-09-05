<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Group teaching: one tutor, one subject, several students.
     *
     * Every other flow assumes one booking is one student, and that assumption
     * is worth keeping — it is what the payout ledger and its invariants are
     * built on. So a class does not replace bookings: each enrolled student
     * still gets their own booking and their own payment, and the class is the
     * thing that groups them and decides what the tutor is owed in total.
     */
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();

            // Null for an online group; a place to travel to for a centre group.
            $table->foreignId('centre_id')->nullable()->constrained('centres')->nullOnDelete();

            $table->string('delivery_mode', 32);
            $table->string('title')->nullable();

            $table->string('schedule_day', 16)->nullable();
            $table->time('schedule_time')->nullable();
            $table->decimal('duration_hours', 4, 1)->default(1.5);
            $table->unsignedSmallInteger('total_sessions')->default(1);
            $table->date('starts_on')->nullable();

            $table->unsignedSmallInteger('capacity')->default(8);
            $table->decimal('price_per_student', 10, 2);

            // How the tutor is paid. The parameters are only read by the models
            // that use them, so switching model does not need a migration.
            $table->string('payout_model', 32)->default('per_student');
            $table->decimal('payout_base', 10, 2)->nullable();
            $table->decimal('payout_per_head', 10, 2)->nullable();
            $table->unsignedSmallInteger('payout_head_threshold')->nullable();

            $table->enum('status', ['draft', 'open', 'closed', 'completed', 'cancelled'])->default('draft');
            $table->timestamps();

            $table->index(['status', 'delivery_mode']);
        });

        Schema::create('class_enrolments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained('class_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();

            // The student's own booking and payment, created when they enrol,
            // so group revenue flows through exactly the same ledger as
            // one-to-one work.
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();

            $table->enum('status', ['pending', 'active', 'cancelled'])->default('pending');
            $table->timestamps();

            // A student cannot take the same seat twice.
            $table->unique(['class_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_enrolments');
        Schema::dropIfExists('class_sessions');
    }
};
