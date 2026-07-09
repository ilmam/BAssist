<?php

use App\Support\CrudRouteRegistrar;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/', function () {
        $homeRoute = CrudRouteRegistrar::homeRouteNameFor(auth()->user());

        return $homeRoute ? redirect()->route($homeRoute) : abort(403);
    });

    Route::middleware('entity.access')->group(function (): void {
        CrudRouteRegistrar::registerWebRoutes();
    });
});

Route::view('theme-test', 'pages.theme-test')->name('theme.test');
