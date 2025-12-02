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

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register User Observer for super admin protection
        \App\Models\User::observe(\App\Observers\UserObserver::class);

        // Register Setting Observer for automatic cache clearing
        \App\Models\Setting::observe(\App\Observers\SettingObserver::class);
    }
}
