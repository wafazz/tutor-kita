<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_sessions', function (Blueprint $table) {
            $table->json('proof_photos')->nullable()->after('tutor_notes');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_sessions', function (Blueprint $table) {
            $table->dropColumn('proof_photos');
        });
    }
};
