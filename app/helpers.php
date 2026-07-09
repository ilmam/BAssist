<?php

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFactory;

if (! function_exists('ui_theme')) {
    function ui_theme(): string
    {
        $theme = config('ui.theme', 'metronic9');
        $themes = config('ui.themes', []);

        if (! array_key_exists($theme, $themes)) {
            return 'metronic9';
        }

        return $theme;
    }
}

if (! function_exists('ui_layout')) {
    function ui_layout(): string
    {
        return config('ui.themes.'.ui_theme().'.layout');
    }
}

if (! function_exists('ui_asset')) {
    function ui_asset(string $path = ''): string
    {
        $prefix = config('ui.themes.'.ui_theme().'.asset_prefix');

        if ($path === '') {
            return asset($prefix);
        }

        return asset($prefix.'/'.ltrim($path, '/'));
    }
}

if (! function_exists('ui_component_view')) {
    function ui_component_view(string $component): View
    {
        $view = 'themes.'.ui_theme().'.components.'.$component;

        if (! ViewFactory::exists($view)) {
            throw new InvalidArgumentException("UI component view [{$view}] not found.");
        }

        return view($view);
    }
}

if (! function_exists('nav_items')) {
    function nav_items(): array
    {
        $items = config('navigation.items', []);
        $entityItems = [];

        foreach (\App\Support\CrudEntityRegistry::all() as $model => $options) {
            if (! ($options['nav'] ?? false)) {
                continue;
            }

            if (! entity_can($model, \App\Support\EntityAccess::VIEW)) {
                continue;
            }

            $entityItems[] = [
                'label' => $options['nav_label'] ?? \Illuminate\Support\Str::plural($model),
                'route' => model_route_name($model, 'index'),
                'icon' => $options['nav_icon'] ?? 'element-11',
                'icon_v8' => $options['nav_icon_v8'] ?? ($options['nav_icon'] ?? 'element-11'),
            ];
        }

        if ($entityItems !== []) {
            $items[] = [
                'label' => config('navigation.entities.label', 'Entities'),
                'icon' => config('navigation.entities.icon', 'element-plus'),
                'icon_v8' => config('navigation.entities.icon_v8', config('navigation.entities.icon', 'element-plus')),
                'children' => $entityItems,
            ];
        }

        $administration = config('navigation.administration');

        if ($administration && \App\Support\EntityAccess::isSuperAdmin(auth()->user())) {
            $items[] = [
                'label' => $administration['label'],
                'icon' => $administration['icon'] ?? 'setting-2',
                'icon_v8' => $administration['icon_v8'] ?? ($administration['icon'] ?? 'setting-2'),
                'children' => $administration['children'] ?? [],
            ];
        }

        return $items;
    }
}

if (! function_exists('nav_is_active')) {
    function nav_is_active(string|array|null $route): bool
    {
        if (is_array($route)) {
            foreach ($route as $childRoute) {
                if (nav_is_active($childRoute)) {
                    return true;
                }
            }

            return false;
        }

        if ($route === null || $route === '') {
            return false;
        }

        return request()->routeIs($route) || request()->routeIs($route.'.*');
    }
}

if (! function_exists('nav_item_is_active')) {
    function nav_item_is_active(array $item): bool
    {
        if (! empty($item['children'])) {
            return nav_is_active(array_column($item['children'], 'route'));
        }

        return nav_is_active($item['route'] ?? null);
    }
}

if (! function_exists('ui_form_view')) {
    function ui_form_view(string $control): string
    {
        return 'themes.'.ui_theme().'.controls.form.'.$control;
    }
}

if (! function_exists('model_page_view')) {
    function model_page_view(string $model, string $action): string
    {
        $defaults = [
            'list' => 'pages.generic.list',
            'form' => 'pages.generic.form',
            'details' => 'pages.generic.details',
        ];

        if (! array_key_exists($action, $defaults)) {
            throw new InvalidArgumentException("Unknown model page action [{$action}].");
        }

        $resource = \Illuminate\Support\Str::plural(\Illuminate\Support\Str::snake($model));
        $override = "pages.{$resource}.{$action}";

        if (ViewFactory::exists($override)) {
            return $override;
        }

        $configured = config("crud.models.{$model}.views.{$action}");

        if (is_string($configured) && ViewFactory::exists($configured)) {
            return $configured;
        }

        return $defaults[$action];
    }
}

if (! function_exists('model_modal_view')) {
    function model_modal_view(string $model, string $action): string
    {
        $defaults = [
            'view' => 'pages.modals.view',
            'form' => 'pages.modals.form',
            'delete' => 'pages.modals.delete',
        ];

        if (! array_key_exists($action, $defaults)) {
            throw new InvalidArgumentException("Unknown model modal action [{$action}].");
        }

        $resource = \Illuminate\Support\Str::plural(\Illuminate\Support\Str::snake($model));
        $override = "pages.{$resource}.modals.{$action}";

        if (ViewFactory::exists($override)) {
            return $override;
        }

        $configured = config("crud.models.{$model}.modals.{$action}");

        if (is_string($configured) && ViewFactory::exists($configured)) {
            return $configured;
        }

        return $defaults[$action];
    }
}

if (! function_exists('model_route_name')) {
    function model_route_name(string $model, string $action = 'index'): string
    {
        return \Illuminate\Support\Str::plural(\Illuminate\Support\Str::snake($model)).'.'.$action;
    }
}

if (! function_exists('model_route')) {
    function model_route(string $model, string $action = 'index', mixed $parameters = []): string
    {
        return route(model_route_name($model, $action), $parameters);
    }
}

if (! function_exists('model_modal_path')) {
    function model_modal_path(string $model, string $action, int|string $id): string
    {
        $resource = \Illuminate\Support\Str::plural(\Illuminate\Support\Str::snake($model));

        return url($resource.'/modal/'.$id.'/'.$action);
    }
}

if (! function_exists('entity_can')) {
    function entity_can(string $entity, string $ability): bool
    {
        return \App\Support\EntityAccess::can(auth()->user(), $entity, $ability);
    }
}

if (! function_exists('is_super_admin')) {
    function is_super_admin(): bool
    {
        return \App\Support\EntityAccess::isSuperAdmin(auth()->user());
    }
}
