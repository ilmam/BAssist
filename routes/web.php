<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProjectExportController;
use App\Http\Controllers\TraceabilityController;
use App\Support\CrudRouteRegistrar;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/', function () {
        $homeRoute = CrudRouteRegistrar::homeRouteNameFor(auth()->user());

        return $homeRoute ? redirect()->route($homeRoute) : abort(403);
    });

    Route::get('traceability', [TraceabilityController::class, 'index'])->name('traceability.index');
    Route::get('traceability/export', [TraceabilityController::class, 'export'])->name('traceability.export');
    Route::get('projects/{project}/export', [ProjectExportController::class, 'show'])->name('projects.export');

    Route::middleware('super.admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::resource('users', UserController::class)->only(['index', 'edit', 'update']);
    });

    Route::middleware('entity.access')->group(function (): void {
        CrudRouteRegistrar::registerWebRoutes();
    });
});

Route::view('theme-test', 'pages.theme-test')->name('theme.test');
