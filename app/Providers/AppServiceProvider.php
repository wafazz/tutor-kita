<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        if (Schema::hasTable('settings')) {
            $resendApiKey = Setting::get('resend_api_key');
            if ($resendApiKey) {
                Config::set('mail.default', 'smtp');
                Config::set('mail.mailers.smtp.host', 'smtp.resend.com');
                Config::set('mail.mailers.smtp.port', 465);
                Config::set('mail.mailers.smtp.username', 'resend');
                Config::set('mail.mailers.smtp.password', $resendApiKey);
                Config::set('mail.mailers.smtp.encryption', 'tls');
                Config::set('mail.from.address', Setting::get('resend_from_email', 'noreply@tutorkita.com'));
                Config::set('mail.from.name', Setting::get('resend_from_name', 'TutorKita'));
            }
        }
    }
}
