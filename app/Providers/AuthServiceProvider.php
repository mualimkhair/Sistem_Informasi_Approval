<?php

namespace App\Providers;

use App\Models\PengajuanCuti;
use App\Models\User;
use App\Policies\PengajuanCutiPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        PengajuanCuti::class => PengajuanCutiPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
