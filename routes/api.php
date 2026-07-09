<?php

use App\Support\CrudRouteRegistrar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::middleware('entity.access')->group(function (): void {
        CrudRouteRegistrar::registerApiRoutes();
    });
});
