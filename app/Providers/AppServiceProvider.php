<?php

namespace App\Providers;

use App\Commerce\Contracts\ProductCatalog;
use App\Commerce\Infrastructure\EloquentProductCatalog;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductCatalog::class, EloquentProductCatalog::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
