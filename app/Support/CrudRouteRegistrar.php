<?php

namespace App\Support;

use App\Http\Controllers\Api\CrudController as ApiCrudController;
use App\Http\Controllers\CrudController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class CrudRouteRegistrar
{
    public static function registerWebRoutes(): void
    {
        foreach (CrudEntityRegistry::all() as $model => $options) {
            self::registerWebRoutesForModel($model, $options);
        }
    }

    public static function registerApiRoutes(): void
    {
        Route::group(['as' => 'api.'], function (): void {
            foreach (CrudEntityRegistry::all() as $model => $options) {
                self::registerApiRoutesForModel($model, $options);
            }
        });
    }

    public static function homeRouteName(): ?string
    {
        foreach (CrudEntityRegistry::all() as $model => $options) {
            if ($options['home'] ?? false) {
                return model_route_name($model, 'index');
            }
        }

        $firstModel = array_key_first(CrudEntityRegistry::all());

        return $firstModel ? model_route_name($firstModel, 'index') : null;
    }

    protected static function registerWebRoutesForModel(string $model, array $options): void
    {
        $resource = CrudEntityRegistry::resourceName($model);
        $controller = $options['controller'] ?? CrudController::class;
        $modalActions = $options['modal_actions'] ?? ['view', 'edit', 'delete'];

        foreach ($modalActions as $action) {
            $method = self::modalMethod($action);

            if ($method === null) {
                continue;
            }

            Route::get("{$resource}/modal/{id}/{$action}", [$controller, $method])
                ->name("{$resource}.modal{$action}");
        }

        if (in_array('delete', $modalActions, true)) {
            Route::get("{$resource}/modal/{id}", [$controller, 'modalShow'])
                ->name("{$resource}.modalshow");
        }

        Route::resource($resource, $controller);
    }

    protected static function registerApiRoutesForModel(string $model, array $options): void
    {
        $resource = CrudEntityRegistry::resourceName($model);
        $controller = $options['api_controller'] ?? ApiCrudController::class;

        Route::resource($resource, $controller)->names(Str::snake($model));
    }

    protected static function modalMethod(string $action): ?string
    {
        return match ($action) {
            'view' => 'modalView',
            'edit' => 'modalEdit',
            'delete' => 'modalDelete',
            default => null,
        };
    }
}
