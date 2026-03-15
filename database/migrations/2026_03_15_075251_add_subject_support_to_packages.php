<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->enum('package_type', ['all', 'specific'])->default('all')->after('name');
        });

        Schema::create('package_subject', function (Blueprint $table) {
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->primary(['package_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_subject');

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('package_type');
        });
    }
};
