<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;

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
        if (app()->environment('production')) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        app()->setLocale(session('locale', config('app.locale', 'vi')));

        // GRACE: Chien thuat "Stateless & SSL Bypass" - Giai quyet dut diem loi Login Google local
        if (config('app.env') === 'local') {
            Socialite::extend('google', function ($app) {
                $config = $app['config']['services.google'];
                
                // Su dung GoogleProvider goc nhung ghi de co che khoi tao Guzzle
                $provider = $app->make(\Laravel\Socialite\SocialiteManager::class)->buildProvider(
                    \Laravel\Socialite\Two\GoogleProvider::class,
                    $config
                );

                // Ep buoc dung Stateless de tranh loi session mismatch va bypass SSL
                return $provider->stateless()->setHttpClient(new \GuzzleHttp\Client([
                    'verify' => false,
                    'timeout' => 30,
                ]));
            });
        }
    }
}
