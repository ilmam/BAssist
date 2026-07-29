<?php

use App\Helpers\Ui;
use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
use App\Support\HelpRegistry;
use App\Support\NavTreeBuilder;
use App\Support\ProjectContext;
use App\Support\WorkspaceContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\Support\Str;

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
        $items = [];

        foreach (config('navigation.items', []) as $item) {
            if (! nav_item_is_visible($item)) {
                continue;
            }

            $items[] = nav_item_with_sticky_query($item);
        }

        foreach (app(NavTreeBuilder::class)->build() as $hierarchyItem) {
            $items[] = $hierarchyItem;
        }

        $administration = config('navigation.administration');

        if ($administration && EntityAccess::isSuperAdmin(auth()->user())) {
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

if (! function_exists('nav_item_with_sticky_query')) {
    /**
     * Attach sticky workspace/project query params to top-level links that benefit from scope.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    function nav_item_with_sticky_query(array $item): array
    {
        $route = $item['route'] ?? null;
        if (in_array($route, ['traceability.index', 'acceptance-plan.index', 'diagrams.index'], true)) {
            $query = [];
            $workspaceId = app(WorkspaceContext::class)->id();
            $projectId = app(ProjectContext::class)->id();

            if ($workspaceId !== null) {
                $query['workspace_id'] = $workspaceId;
            }
            if ($projectId !== null) {
                $query['project_id'] = $projectId;
            }

            if ($query !== []) {
                $item['query'] = array_merge($item['query'] ?? [], $query);
            }
        }

        return $item;
    }
}

if (! function_exists('nav_url')) {
    /**
     * @param  array<string, mixed>  $item
     */
    function nav_url(array $item): string
    {
        $route = $item['route'] ?? null;
        if ($route === null || $route === '') {
            return '#';
        }

        $url = route($route, $item['route_params'] ?? []);
        $query = $item['query'] ?? [];

        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($query);
        }

        return $url;
    }
}

if (! function_exists('nav_item_is_visible')) {
    function nav_item_is_visible(array $item): bool
    {
        $entities = $item['entities'] ?? null;

        if (! is_array($entities) || $entities === []) {
            return true;
        }

        foreach ($entities as $entity) {
            if (entity_can((string) $entity, EntityAccess::VIEW)) {
                return true;
            }
        }

        return false;
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
            foreach ($item['children'] as $child) {
                if (nav_item_is_active($child)) {
                    return true;
                }
            }
        }

        if (! nav_is_active($item['route'] ?? null)) {
            return false;
        }

        return nav_item_context_matches($item);
    }
}

if (! function_exists('nav_item_is_open')) {
    /**
     * Whether an accordion should expand (active route or sticky context).
     *
     * @param  array<string, mixed>  $item
     */
    function nav_item_is_open(array $item): bool
    {
        if (! empty($item['force_open'])) {
            return true;
        }

        if (! empty($item['children'])) {
            foreach ($item['children'] as $child) {
                if (nav_item_is_open($child)) {
                    return true;
                }
            }
        }

        return nav_item_is_active($item);
    }
}

if (! function_exists('nav_item_context_matches')) {
    /**
     * When a nav item carries workspace/project context, require it to match sticky/request scope.
     *
     * @param  array<string, mixed>  $item
     */
    function nav_item_context_matches(array $item): bool
    {
        $context = $item['context'] ?? [];
        if ($context === []) {
            return true;
        }

        if (array_key_exists('project_id', $context)) {
            $activeProject = app(ProjectContext::class)->id()
                ?? (is_numeric(request('project_id')) ? (int) request('project_id') : null);

            return $activeProject !== null && (int) $context['project_id'] === $activeProject;
        }

        if (array_key_exists('workspace_id', $context)) {
            $activeWorkspace = app(WorkspaceContext::class)->id()
                ?? (is_numeric(request('workspace_id')) ? (int) request('workspace_id') : null);

            return $activeWorkspace !== null && (int) $context['workspace_id'] === $activeWorkspace;
        }

        return true;
    }
}

if (! function_exists('ui_form_view')) {
    function ui_form_view(string $control): string
    {
        return 'themes.'.ui_theme().'.controls.form.'.$control;
    }
}

if (! function_exists('ui_form_field_layout_vars')) {
    /**
     * Shared layout defaults for Metronic form controls.
     * Blade @include of _vars cannot export locals into the parent view, so
     * FormBuilder merges these when rendering bs* components, and controls
     * may also extract() them in-scope as a fallback.
     *
     * fieldStackClass / fieldRowClass drive the per-field wrapper. Sibling and
     * label spacing live in ui-layout.css. Opt into compact density on Form::open
     * (quick-create forms do this automatically):
     *   'attributes' => ['data-form-density' => 'compact']
     * (also accepts class kt-form--compact, .form-fields--compact, or
     * .form-fields-grid under a compact form — grid uses row-gap, not margins)
     */
    function ui_form_field_layout_vars(string $name = '', ?array $attributes = null): array
    {
        $attributes = $attributes ?? [];

        return [
            'attributes' => $attributes,
            'horizontal' => ($attributes['layout'] ?? 'v') === 'h',
            'labelText' => Ui::fieldLabel($name),
            'fieldStackClass' => 'kt-form-item',
            'fieldRowClass' => 'kt-form-field-row flex flex-col lg:flex-row lg:items-start gap-2.5',
        ];
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

        $resource = Str::plural(Str::snake($model));
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
            'quick-create' => 'pages.modals.quick-create',
        ];

        if (! array_key_exists($action, $defaults)) {
            throw new InvalidArgumentException("Unknown model modal action [{$action}].");
        }

        $resource = Str::plural(Str::snake($model));
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
        return Str::plural(Str::snake($model)).'.'.$action;
    }
}

if (! function_exists('model_route')) {
    function model_route(string $model, string $action = 'index', mixed $parameters = []): string
    {
        return route(model_route_name($model, $action), $parameters);
    }
}

if (! function_exists('model_modal_path')) {
    function model_modal_path(string $model, string $action, int|string|null $id = null): string
    {
        $resource = Str::plural(Str::snake($model));

        if ($action === 'create') {
            return url($resource.'/modal/create');
        }

        if ($action === 'quick-create' || $action === 'quickcreate') {
            return url($resource.'/modal/quick-create');
        }

        if ($id === null || $id === '') {
            throw new InvalidArgumentException("Modal action [{$action}] requires an id.");
        }

        return url($resource.'/modal/'.$id.'/'.$action);
    }
}

if (! function_exists('help_exists')) {
    function help_exists(string $modelOrKey): bool
    {
        $registry = HelpRegistry::class;

        if (array_key_exists(class_basename($modelOrKey), CrudEntityRegistry::all())) {
            return $registry::existsForModel($modelOrKey);
        }

        return $registry::exists($modelOrKey);
    }
}

if (! function_exists('help_url')) {
    function help_url(string $modelOrKey): ?string
    {
        if (! help_exists($modelOrKey)) {
            return null;
        }

        if (array_key_exists(class_basename($modelOrKey), CrudEntityRegistry::all())) {
            return model_route($modelOrKey, 'help');
        }

        $key = HelpRegistry::normalizeKey($modelOrKey);

        if (! Route::has($key.'.help')) {
            return null;
        }

        return route($key.'.help');
    }
}

if (! function_exists('entity_can')) {
    function entity_can(string $entity, string $ability): bool
    {
        return EntityAccess::can(auth()->user(), $entity, $ability);
    }
}

if (! function_exists('is_super_admin')) {
    function is_super_admin(): bool
    {
        return EntityAccess::isSuperAdmin(auth()->user());
    }
}
