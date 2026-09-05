<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STATUSES = ['pending', 'success', 'failed', 'refunded', 'expired'];

    /**
     * A payment that timed out is not the same as one that failed.
     *
     * Failed means the bank refused it. Expired means nobody tried in time,
     * and a tutor was held meanwhile. Keeping them apart matters for deciding
     * whether to chase the parent or re-match the request.
     */
    public function up(): void
    {
        $this->setStatuses(self::STATUSES);
    }

    public function down(): void
    {
        DB::table('payments')->where('status', 'expired')->update(['status' => 'failed']);

        $this->setStatuses(['pending', 'success', 'failed', 'refunded']);
    }

    /**
     * Written per driver: MODIFY COLUMN is MySQL syntax, and the test suite
     * runs on SQLite, where the column has to be rebuilt instead.
     */
    private function setStatuses(array $statuses): void
    {
        if (DB::getDriverName() === 'mysql') {
            $list = implode(',', array_map(fn ($s) => "'{$s}'", $statuses));

            DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM({$list}) NOT NULL DEFAULT 'pending'");

            return;
        }

        Schema::table('payments', function (Blueprint $table) use ($statuses) {
            $table->enum('status', $statuses)->default('pending')->change();
        });
    }
};
