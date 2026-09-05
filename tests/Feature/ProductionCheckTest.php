<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Settings that are correct in development and dangerous in production are
 * exactly the ones that survive review and then leak. This refuses the deploy
 * rather than relying on someone remembering.
 */
class ProductionCheckTest extends TestCase
{
    use RefreshDatabase;

    private function productionConfig(array $overrides = []): void
    {
        Config::set(array_merge([
            'app.debug' => false,
            'app.env' => 'production',
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'app.url' => 'https://tutorhub.my',
            'session.secure' => true,
            'logging.default' => 'daily',
            'queue.default' => 'database',
        ], $overrides));

        Setting::set('payments_manual_mode', '0');
        Setting::set('billplz_api_key', 'k');
        Setting::set('billplz_collection_id', 'c');
        Setting::set('billplz_x_signature_key', 's');
        Setting::set('billplz_sandbox', '0');
    }

    public function test_a_correctly_configured_deployment_passes(): void
    {
        $this->productionConfig();

        $this->artisan('app:check-production')
            ->expectsOutputToContain('Safe to deploy.')
            ->assertSuccessful();
    }

    public function test_debug_mode_blocks_the_deploy(): void
    {
        $this->productionConfig(['app.debug' => true]);

        $this->artisan('app:check-production')
            ->expectsOutputToContain('APP_DEBUG is on')
            ->assertFailed();
    }

    public function test_a_development_url_blocks_the_deploy(): void
    {
        $this->productionConfig(['app.url' => 'http://localhost']);

        $this->artisan('app:check-production')->assertFailed();
    }

    public function test_manual_payment_mode_blocks_the_deploy(): void
    {
        $this->productionConfig();
        Setting::set('payments_manual_mode', '1');

        // Invoices could be settled without collecting anything.
        $this->artisan('app:check-production')
            ->expectsOutputToContain('Manual payment mode is ON')
            ->assertFailed();
    }

    public function test_a_missing_signature_key_blocks_the_deploy(): void
    {
        $this->productionConfig();
        Setting::set('billplz_x_signature_key', '');

        // Without it every payment notification is rejected, so nothing is
        // ever confirmed.
        $this->artisan('app:check-production')
            ->expectsOutputToContain('X-Signature key is missing')
            ->assertFailed();
    }

    public function test_the_sandbox_gateway_warns_without_blocking(): void
    {
        $this->productionConfig();
        Setting::set('billplz_sandbox', '1');

        $this->artisan('app:check-production')
            ->expectsOutputToContain('still pointing at the sandbox')
            ->assertSuccessful();
    }

    public function test_strict_mode_treats_a_warning_as_blocking(): void
    {
        $this->productionConfig();
        Setting::set('billplz_sandbox', '1');

        $this->artisan('app:check-production --strict')->assertFailed();
    }
}
