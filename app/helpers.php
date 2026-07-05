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
        return config('navigation.items', []);
    }
}

if (! function_exists('nav_is_active')) {
    function nav_is_active(string $route): bool
    {
        return request()->routeIs($route) || request()->routeIs($route.'.*');
    }
}

if (! function_exists('ui_form_view')) {
    function ui_form_view(string $control): string
    {
        return 'themes.'.ui_theme().'.controls.form.'.$control;
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
