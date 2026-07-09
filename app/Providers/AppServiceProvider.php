<?php

namespace App\Providers;

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

    public function boot(): void
    {
        if (file_exists(storage_path('app/mail_setup.json'))) {
            $settings = json_decode(file_get_contents(storage_path('app/mail_setup.json')), true);
            if ($settings) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $settings['host'] ?? config('mail.mailers.smtp.host'),
                    'mail.mailers.smtp.port' => $settings['port'] ?? config('mail.mailers.smtp.port'),
                    'mail.mailers.smtp.username' => $settings['username'] ?? config('mail.mailers.smtp.username'),
                    'mail.mailers.smtp.password' => $settings['password'] ?? config('mail.mailers.smtp.password'),
                    'mail.mailers.smtp.encryption' => ($settings['encryption'] ?? 'none') === 'none' ? null : ($settings['encryption'] ?? null),
                    'mail.from.address' => $settings['from_address'] ?? config('mail.from.address'),
                    'mail.from.name' => $settings['from_name'] ?? config('mail.from.name'),
                ]);
            }
        }
    }
}
