<?php

namespace App\Providers;

use App\Services\CreditScoringService;
use Illuminate\Support\ServiceProvider;

class CreditScoringServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CreditScoringService::class, function ($app) {
            return new CreditScoringService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
