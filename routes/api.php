<?php

use App\Http\Controllers\Api\FeatureGherkinController;
use App\Http\Controllers\Api\LineageController;
use App\Http\Controllers\Api\ProjectInsightsController;
use App\Support\CrudRouteRegistrar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Signed in by browser session or by personal API token (docs/api-platform.md).
// api.ability confines a token to what it was issued for (read / write).
Route::middleware(['auth:sanctum', 'api.ability'])->group(function (): void {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Derived, read-only views. Each controller authorizes like its web page.
    Route::get('projects/{project}/readiness', [ProjectInsightsController::class, 'readiness'])
        ->name('api.projects.readiness');
    Route::get('projects/{project}/traceability', [ProjectInsightsController::class, 'traceability'])
        ->name('api.projects.traceability');
    Route::get('projects/{project}/acceptance-plan', [ProjectInsightsController::class, 'acceptancePlan'])
        ->name('api.projects.acceptance-plan');
    Route::get('projects/{project}/gherkin', [ProjectInsightsController::class, 'gherkin'])
        ->name('api.projects.gherkin');
    Route::get('features/{id}/gherkin', [FeatureGherkinController::class, 'show'])
        ->whereNumber('id')
        ->name('api.features.gherkin');
    Route::get('{resource}/{id}/lineage', [LineageController::class, 'show'])
        ->whereNumber('id')
        ->name('api.lineage.show');

    Route::middleware('entity.access')->group(function (): void {
        CrudRouteRegistrar::registerApiRoutes();
    });
});
