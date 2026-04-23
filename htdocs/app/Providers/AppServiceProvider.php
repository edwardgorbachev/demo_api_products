<?php

namespace App\Providers;

use App\Contracts\ProductSearchServiceInterface;
use App\Services\ProductElasticSearchService;
use App\Services\ProductSearchService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Переключение через .env: SEARCH_DRIVER=elastic или mysql
        $this->app->bind(ProductSearchServiceInterface::class, function () {
            return config('services.search.driver') === 'elastic'
                ? $this->app->make(ProductElasticSearchService::class)
                : $this->app->make(ProductSearchService::class);
        });
    }

    public function boot(): void
    {
        //
    }
}
