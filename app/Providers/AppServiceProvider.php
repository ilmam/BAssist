<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One per request: how the request arrived (web, API token, MCP).
        $this->app->scoped(\App\Support\RequestChannel::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // History + approval reset: every model marked #[Tracked] or #[Approvable] is observed.
        $observed = array_unique(array_merge(
            \App\Support\EntityFeatures::models(\App\Attributes\Tracked::class),
            \App\Support\EntityFeatures::models(\App\Attributes\Approvable::class),
        ));
        foreach ($observed as $model) {
            ('App\\Models\\'.$model)::observe(\App\Observers\TrackedEntityObserver::class);
        }
    }
}
