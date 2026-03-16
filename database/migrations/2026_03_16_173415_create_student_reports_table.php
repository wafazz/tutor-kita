<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('tutor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->string('exam_type'); // e.g. Monthly Test, Mid-term, Final, Quiz, Assessment
            $table->string('term')->nullable(); // e.g. Term 1, Term 2, Semester 1
            $table->decimal('score', 5, 2); // e.g. 85.50
            $table->decimal('total_marks', 5, 2)->default(100);
            $table->string('grade')->nullable(); // A+, A, B+, etc
            $table->date('exam_date')->nullable();
            $table->text('remarks')->nullable(); // tutor's comments
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_reports');
    }
};
