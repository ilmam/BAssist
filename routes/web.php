<?php

use App\Support\CrudRouteRegistrar;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $homeRoute = CrudRouteRegistrar::homeRouteName();

    return $homeRoute ? redirect()->route($homeRoute) : abort(404);
});

Route::view('theme-test', 'pages.theme-test')->name('theme.test');

CrudRouteRegistrar::registerWebRoutes();
