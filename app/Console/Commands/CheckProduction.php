<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Support\Payments\Billplz;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

/**
 * Refuses a deployment that would expose or mis-handle real data.
 *
 * These are settings that are correct in development and dangerous in
 * production, which is exactly the combination that survives a code review and
 * then leaks. Meant to run as a deploy step, where a non-zero exit stops the
 * release.
 */
class CheckProduction extends Command
{
    protected $signature = 'app:check-production {--strict : treat warnings as failures too}';

    protected $description = 'Verify the configuration is safe to run in production';

    private array $failures = [];

    private array $warnings = [];

    public function handle(Billplz $billplz): int
    {
        $this->line('Checking production readiness…');
        $this->newLine();

        // Debug mode prints stack traces, environment variables and database
        // credentials to anyone who triggers an error.
        $this->must(! Config::get('app.debug'), 'APP_DEBUG is on — errors would expose configuration and credentials');

        $this->must(Config::get('app.env') === 'production', "APP_ENV is '".Config::get('app.env')."', not production");
        $this->must(filled(Config::get('app.key')), 'APP_KEY is not set — sessions and encrypted columns cannot work');

        $url = (string) Config::get('app.url');
        $this->must(str_starts_with($url, 'https://'), "APP_URL is '{$url}' — it must be https, or the gateway callback and cookies are wrong");
        $this->must(! str_contains($url, 'localhost') && ! str_contains($url, '.test'), "APP_URL still points at a development host: {$url}");

        // Bank details are encrypted with the app key; losing session integrity
        // over plain http would undo that.
        $this->must(Config::get('session.secure') !== false || str_starts_with($url, 'https://'),
            'Session cookies are not marked secure');

        $this->must(Setting::get('payments_manual_mode', '0') !== '1',
            'Manual payment mode is ON — invoices can be settled without collecting any money');

        if ($billplz->configured()) {
            $this->must(filled($billplz->signatureKey()),
                'Billplz X-Signature key is missing — every payment notification would be rejected');
            $this->should(! $billplz->sandbox(), 'Billplz is still pointing at the sandbox');
        } else {
            $this->should(false, 'No payment gateway is configured — nothing can be collected');
        }

        $this->should(Config::get('logging.default') !== 'single' || Config::get('app.env') !== 'production',
            'Logging to a single file; consider daily or a log service so it can be searched and rotated');

        $this->should(Config::get('queue.default') !== 'sync',
            'Queue is sync — slow work runs inside the web request');

        $this->newLine();

        foreach ($this->failures as $failure) {
            $this->line("  <fg=red>✗</> {$failure}");
        }

        foreach ($this->warnings as $warning) {
            $this->line("  <fg=yellow>!</> {$warning}");
        }

        if ($this->failures === [] && $this->warnings === []) {
            $this->info('Safe to deploy.');

            return self::SUCCESS;
        }

        $this->newLine();

        $blocking = $this->failures !== [] || ($this->option('strict') && $this->warnings !== []);

        if ($blocking) {
            $this->error(count($this->failures).' blocking issue(s), '.count($this->warnings).' warning(s).');

            return self::FAILURE;
        }

        $this->warn(count($this->warnings).' warning(s), none blocking.');

        return self::SUCCESS;
    }

    private function must(bool $condition, string $message): void
    {
        if (! $condition) {
            $this->failures[] = $message;
        }
    }

    private function should(bool $condition, string $message): void
    {
        if (! $condition) {
            $this->warnings[] = $message;
        }
    }
}
