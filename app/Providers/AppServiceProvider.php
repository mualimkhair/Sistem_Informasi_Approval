<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
    }
}
