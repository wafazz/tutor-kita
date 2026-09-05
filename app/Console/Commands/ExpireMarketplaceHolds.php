<?php

namespace App\Console\Commands;

use App\Support\MarketplaceExpiry;
use Illuminate\Console\Command;

/**
 * Releases tutors and seats that have been held too long.
 *
 * Meant to run on a schedule. Without it a single parent who never pays keeps
 * a tutor out of the market indefinitely.
 */
class ExpireMarketplaceHolds extends Command
{
    protected $signature = 'marketplace:expire {--dry-run : report what would be released without changing anything}';

    protected $description = 'Reopen requests and release seats whose acceptance or payment window has passed';

    public function handle(MarketplaceExpiry $expiry): int
    {
        $this->line("Acceptance window: {$expiry->acceptanceHours()}h, payment window: {$expiry->paymentHours()}h.");

        if ($this->option('dry-run')) {
            $this->warn('Dry run: nothing will be changed.');
            $this->line('  (run without --dry-run to release them)');

            return self::SUCCESS;
        }

        $result = $expiry->sweep();

        $this->line("  {$result['acceptances']} request(s) reopened after no answer from the tutor");
        $this->line("  {$result['payments']} unpaid payment(s) expired");
        $this->line("  {$result['seats']} held class seat(s) released");

        $total = array_sum($result);

        $this->info($total === 0 ? 'Nothing was being held too long.' : "{$total} hold(s) released.");

        return self::SUCCESS;
    }
}
