<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_requests', function (Blueprint $table) {
            $table->uuid('request_group')->nullable()->after('id');
            $table->index('request_group');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_requests', function (Blueprint $table) {
            $table->dropIndex(['request_group']);
            $table->dropColumn('request_group');
        });
    }
};
