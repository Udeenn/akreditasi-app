<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use phpCAS;

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
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        Paginator::useBootstrapFive();
        Gate::define('admin-action', function ($user) {
            return $user->role === 'admin';
        });
        Schema::defaultStringLength(191);

        if($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
        }

        // Initialize phpCAS
        // Initialize phpCAS only for web requests, not console commands
        if (!$this->app->runningInConsole() && !phpCAS::isInitialized()) {
            phpCAS::setVerbose(config('app.debug'));
            
            // Konversi version string ke konstanta CAS
            $version = config('cas.version', '2.0');
            $casVersion = ($version === '3.0') ? CAS_VERSION_3_0 : CAS_VERSION_2_0;
            
            phpCAS::client(
                $casVersion,
                config('cas.host'),
                (int) config('cas.port', 443),
                config('cas.context', '/cas'),
                config('cas.service_base_url', config('app.url'))
            );
            
            // Untuk development/testing, nonaktifkan validasi SSL
            if (config('app.env') === 'local' || config('cas.disable_ssl_validation', false)) {
                phpCAS::setNoCasServerValidation();
            } else {
                phpCAS::setNoCasServerValidation(); 
            }
        }
    }
}
