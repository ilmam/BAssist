<?php

namespace App\Support;

use App\Http\Controllers\Api\CrudController as ApiCrudController;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\HelpController;
use App\Models\User;
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
        return self::homeRouteNameFor(auth()->user());
    }

    public static function homeRouteNameFor(?User $user): ?string
    {
        foreach (CrudEntityRegistry::all() as $model => $options) {
            if (($options['home'] ?? false) && EntityAccess::can($user, $model, EntityAccess::VIEW)) {
                return model_route_name($model, 'index');
            }
        }

        foreach (EntityAccess::entitiesFor($user, EntityAccess::VIEW) as $model) {
            return model_route_name($model, 'index');
        }

        return null;
    }

    protected static function registerWebRoutesForModel(string $model, array $options): void
    {
        $resource = CrudEntityRegistry::resourceName($model);
        $controller = $options['controller'] ?? CrudController::class;
        $modalActions = $options['modal_actions'] ?? ['view', 'edit', 'delete', 'create'];

        // Static path before resource {id} routes so "help" is never treated as an id.
        Route::get("{$resource}/help", [HelpController::class, 'show'])
            ->defaults('helpKey', $resource)
            ->name("{$resource}.help");

        // Create has no record id — register before the {id} modal routes.
        if (in_array('create', $modalActions, true)) {
            Route::get("{$resource}/modal/create", [$controller, 'modalCreate'])
                ->name("{$resource}.modalcreate");
            Route::get("{$resource}/modal/quick-create", [$controller, 'modalQuickCreate'])
                ->name("{$resource}.modalquickcreate");
        }

        foreach ($modalActions as $action) {
            if ($action === 'create') {
                continue;
            }

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
            'create' => 'modalCreate',
            default => null,
        };
    }
}
