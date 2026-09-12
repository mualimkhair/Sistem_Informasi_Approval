<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

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
        \App\Models\UnitKerja::observe(\App\Observers\UnitKerjaObserver::class);
        \App\Models\Seksi::observe(\App\Observers\SeksiObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);

        Gate::policy(\App\Models\PengajuanCuti::class, \App\Policies\PengajuanCutiPolicy::class);

        Auth::extend('tab', function ($app, $name, array $config) {
            $provider = $app['auth']->createUserProvider($config['provider'] ?? null);
            return new \App\Auth\TabGuard($provider);
        });
    }
}
