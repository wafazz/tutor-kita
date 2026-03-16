<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->renameColumn('hourly_rate', 'hourly_rate_home');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->decimal('hourly_rate_online', 8, 2)->default(0)->after('hourly_rate_home');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('hourly_rate_online');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->renameColumn('hourly_rate_home', 'hourly_rate');
        });
    }
};
