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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // #8: history on every commentable entity; approval reset on approvable ones.
        foreach (\App\Services\CommentService::COMMENTABLE as $model) {
            $class = 'App\\Models\\'.$model;
            if (class_exists($class)) {
                $class::observe(\App\Observers\TrackedEntityObserver::class);
            }
        }
    }
}
