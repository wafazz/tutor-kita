<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('tutor_request_id')->nullable()->after('id')->constrained('tutor_requests')->onDelete('set null');
            $table->foreignId('booking_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['tutor_request_id']);
            $table->dropColumn('tutor_request_id');
        });
    }
};
