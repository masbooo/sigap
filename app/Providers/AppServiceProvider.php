<?php

namespace App\Providers;

use App\Supports\Payment\PaymentGateway;
use App\Supports\Payment\PaymentGatewayManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class);
        $this->app->bind(PaymentGateway::class, fn (): PaymentGateway => app(PaymentGatewayManager::class)->gateway());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
