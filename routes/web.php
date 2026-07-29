<?php

use App\Http\Controllers\AcceptancePlanController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ArchitectureController;
use App\Http\Controllers\DiagramsController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\GuardrailsController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\ProjectDashboardController;
use App\Http\Controllers\ProjectExportController;
use App\Http\Controllers\StrategicBaselineController;
use App\Http\Controllers\StrategyController;
use App\Http\Controllers\TraceabilityController;
use App\Support\CrudRouteRegistrar;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('/', function () {
        $homeRoute = CrudRouteRegistrar::homeRouteNameFor(auth()->user());

        return $homeRoute ? redirect()->route($homeRoute) : abort(403);
    });

    Route::get('traceability', [TraceabilityController::class, 'index'])->name('traceability.index');
    Route::get('traceability/help', [HelpController::class, 'show'])->defaults('helpKey', 'traceability')->name('traceability.help');
    Route::get('traceability/export', [TraceabilityController::class, 'export'])->name('traceability.export');
    Route::get('acceptance-plan', [AcceptancePlanController::class, 'index'])->name('acceptance-plan.index');
    Route::get('acceptance-plan/export', [AcceptancePlanController::class, 'export'])->name('acceptance-plan.export');
    Route::get('diagrams', [DiagramsController::class, 'index'])->name('diagrams.index');
    Route::get('diagrams/help', [HelpController::class, 'show'])->defaults('helpKey', 'diagrams')->name('diagrams.help');
    Route::get('guardrails', [GuardrailsController::class, 'index'])->name('guardrails.index');
    Route::get('guardrails/help', [HelpController::class, 'show'])->defaults('helpKey', 'guardrails')->name('guardrails.help');
    Route::get('strategy', [StrategyController::class, 'index'])->name('strategy.index');
    Route::get('strategy/help', [HelpController::class, 'show'])->defaults('helpKey', 'strategy')->name('strategy.help');
    Route::get('projects/{project}/dashboard', [ProjectDashboardController::class, 'show'])->name('projects.dashboard');
    Route::get('projects/{project}/export', [ProjectExportController::class, 'show'])->name('projects.export');

    Route::middleware('super.admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::resource('users', UserController::class)->only(['index', 'edit', 'update']);
    });

    Route::middleware('entity.access')->group(function (): void {
        Route::get('features/{id}/export', [FeatureController::class, 'export'])->name('features.export');
        Route::get('features/{id}/print', [FeatureController::class, 'print'])->name('features.print');
        Route::get('features/{id}/import', [FeatureController::class, 'importForm'])->name('features.import');
        Route::post('features/{id}/import/preview', [FeatureController::class, 'importPreview'])->name('features.import.preview');
        Route::get('features/{id}/import/preview', [FeatureController::class, 'importPreviewShow'])->name('features.import.preview.show');
        Route::post('features/{id}/import/confirm', [FeatureController::class, 'importConfirm'])->name('features.import.confirm');
        Route::get('architectures/for-project/{project}', [ArchitectureController::class, 'forProject'])
            ->name('architectures.for-project');
        Route::get('architectures/{id}/export/dsl', [ArchitectureController::class, 'exportDsl'])
            ->name('architectures.export-dsl');
        Route::get('architectures/{id}/export/json', [ArchitectureController::class, 'exportJson'])
            ->name('architectures.export-json');
        Route::get('strategic_baselines/for-project/{project}', [StrategicBaselineController::class, 'forProject'])
            ->name('strategic_baselines.for-project');
        CrudRouteRegistrar::registerWebRoutes();
    });
});

Route::view('theme-test', 'pages.theme-test')->name('theme.test');
