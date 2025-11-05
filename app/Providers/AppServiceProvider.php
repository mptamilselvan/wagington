<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use App\Models\ProductVariant;
use App\Observers\ProductVariantObserver;


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
        date_default_timezone_set('Asia/Singapore');
        
        // Register ProductVariant observer for auto-processing backorders
        ProductVariant::observe(ProductVariantObserver::class);
    }
}
