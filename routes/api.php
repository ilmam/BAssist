<?php

use App\Support\CrudRouteRegistrar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

CrudRouteRegistrar::registerApiRoutes();
