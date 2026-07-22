<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Presentation helpers for hierarchy / child-link columns on entity list pages.
 * Intentionally kept outside the generic CRUD Datatable defaults.
 */
class ListUi
{
    public const CONTEXT_KEYS = ['workspace_id', 'project_id'];

    /**
     * Which parent context keys to copy from the row for a given drill filter.
     *
     * @return list<string>
     */
    public static function carryKeysForFilter(string $filterParam): array
    {
        return match ($filterParam) {
            'workspace_id' => [],
            'project_id' => ['workspace_id'],
            default => self::CONTEXT_KEYS,
        };
    }

    /**
     * Build a custom datatable column that links to a filtered child list.
     *
     * Carries parent context from the row (workspace/project) and merges
     * any context already present on the current list URL via preserve.
     * Tenant is never carried — it is applied implicitly from the authenticated user.
     *
     * @param  array{
     *   label?: string,
     *   icon?: string,
     *   title?: string,
     *   header?: string,
     *   style?: string,
     *   carry?: list<string>,
     *   preserve?: array<string, mixed>
     * }  $options
     */
    public static function childLinkColumn(string $childModel, string $filterParam, string $countField, array $options = []): array
    {
        $label = $options['label'] ?? Str::headline(Str::snake($childModel));
        $icon = $options['icon'] ?? 'abstract-26';
        $title = $options['title'] ?? $label;
        $header = $options['header'] ?? '';
        $style = $options['style'] ?? 'width: 1%; white-space: nowrap';
        $carry = $options['carry'] ?? self::carryKeysForFilter($filterParam);
        $preserve = self::contextFilters($options['preserve'] ?? []);
        $theme = function_exists('ui_theme') ? ui_theme() : 'metronic8';

        $query = [$filterParam.'={id}'];
        $fields = ['id', $countField];

        foreach (self::CONTEXT_KEYS as $param) {
            if ($param === $filterParam) {
                continue;
            }

            // Prefer sticky URL context when already selected.
            if (array_key_exists($param, $preserve) && $preserve[$param] !== null && $preserve[$param] !== '') {
                $query[] = $param.'='.rawurlencode((string) $preserve[$param]);
                continue;
            }

            // Otherwise copy from the row when this drill should carry it.
            if (! in_array($param, $carry, true) || in_array($param, $fields, true)) {
                continue;
            }

            $query[] = $param.'={'.$param.'}';
            $fields[] = $param;
        }

        $href = e(model_route($childModel, 'index')).'?'.implode('&', $query);

        if ($theme === 'metronic9') {
            $template = '<a href="'.$href.'" class="kt-btn kt-btn-sm kt-btn-ghost gap-1" title="'.e($title).'">'
                .'<i class="ki-filled ki-'.$icon.'"></i>'
                .'<span class="text-xs font-medium">{'.$countField.'}</span>'
                .'</a>';
        } else {
            $template = '<a href="'.$href.'" class="btn btn-sm btn-light btn-active-light-primary" title="'.e($title).'">'
                .'<i class="fa fa-'.$icon.'"></i> '
                .'<span>{'.$countField.'}</span>'
                .'</a>';
        }

        return [
            'custom' => true,
            'name' => $countField,
            'title' => $header !== '' ? $header : $title,
            'style' => $style,
            'template' => $template,
            'fields' => $fields,
        ];
    }

    /**
     * Parent-scope filters that stay sticky while drilling relation filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function contextFilters(array $filters): array
    {
        $context = [];

        foreach (self::CONTEXT_KEYS as $key) {
            if (! array_key_exists($key, $filters) || $filters[$key] === null || $filters[$key] === '') {
                continue;
            }

            $context[$key] = $filters[$key];
        }

        return $context;
    }

    /**
     * Build the Show orphans toggle URL while keeping current filters/context.
     *
     * @param  array<string, mixed>  $listFilters
     * @return array{url: string, active: bool}
     */
    public static function orphansToggle(string $indexUrl, array $listFilters): array
    {
        $active = in_array($listFilters['orphans'] ?? null, [1, '1', true, 'true'], true);

        if ($active) {
            $without = $listFilters;
            unset($without['orphans']);
            $without = array_filter($without, static fn ($value) => $value !== null && $value !== '');

            return [
                'url' => $without === [] ? $indexUrl : $indexUrl.'?'.http_build_query($without),
                'active' => true,
            ];
        }

        $with = array_merge($listFilters, ['orphans' => 1]);

        return [
            'url' => $indexUrl.'?'.http_build_query($with),
            'active' => false,
        ];
    }

    /**
     * Resolve active list filters into a banner-friendly summary.
     *
     * @param  array<string, mixed>  $query
     * @param  list<string>  $allowed
     * @return list<array{param: string, label: string, value: string, clear_url: string}>
     */
    public static function activeFilters(array $query, array $allowed, string $indexUrl): array
    {
        $chips = [];

        foreach ($allowed as $param) {
            if (! array_key_exists($param, $query) || $query[$param] === null || $query[$param] === '') {
                continue;
            }

            $raw = $query[$param];

            if ($param === 'orphans') {
                $truthy = $raw === true || $raw === 1 || $raw === '1' || $raw === 'true';
                if (! $truthy) {
                    continue;
                }

                $chips[] = [
                    'param' => $param,
                    'label' => __('ui.orphans_only'),
                    'value' => __('ui.yes'),
                    'clear_url' => self::urlWithout($indexUrl, $query, [$param]),
                ];

                continue;
            }

            $chips[] = [
                'param' => $param,
                'label' => self::filterLabel($param),
                'value' => self::resolveFilterValue($param, $raw),
                'clear_url' => $param === 'workspace_id'
                    ? self::clearWorkspaceUrl($indexUrl, $query)
                    : self::urlWithout($indexUrl, $query, [$param]),
            ];
        }

        return $chips;
    }

    /**
     * Clear sticky workspace (session) and drop workspace_id from the URL.
     *
     * @param  array<string, mixed>  $query
     */
    protected static function clearWorkspaceUrl(string $indexUrl, array $query): string
    {
        unset($query['workspace_id']);
        $query['clear_workspace'] = 1;

        return $indexUrl.'?'.http_build_query($query);
    }

    protected static function filterLabel(string $param): string
    {
        return Ui::fieldLabel($param);
    }

    protected static function resolveFilterValue(string $param, mixed $raw): string
    {
        if ($param === 'orphans') {
            return (string) $raw;
        }

        if (! is_numeric($raw)) {
            return (string) $raw;
        }

        $modelClass = self::modelClassForFilter($param);
        if ($modelClass === null) {
            return (string) $raw;
        }

        /** @var Model|null $record */
        $record = $modelClass::query()->find((int) $raw);
        if (! $record) {
            return '#'.$raw;
        }

        $display = $record->getAttribute('title')
            ?? $record->getAttribute('name')
            ?? $record->getAttribute($record->getKeyName());

        return (string) $display;
    }

    /**
     * @return class-string<Model>|null
     */
    protected static function modelClassForFilter(string $param): ?string
    {
        $map = [
            'project_id' => \App\Models\Project::class,
            'workspace_id' => \App\Models\Workspace::class,
            'business_objective_id' => \App\Models\BusinessObjective::class,
            'business_need_id' => \App\Models\BusinessNeed::class,
            'stakeholder_id' => \App\Models\Stakeholder::class,
            'stakeholder_need_id' => \App\Models\StakeholderNeed::class,
        ];

        return $map[$param] ?? null;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  list<string>  $remove
     */
    protected static function urlWithout(string $indexUrl, array $query, array $remove): string
    {
        foreach ($remove as $key) {
            unset($query[$key]);
        }

        $query = array_filter($query, fn ($value) => $value !== null && $value !== '');

        if ($query === []) {
            return $indexUrl;
        }

        return $indexUrl.'?'.http_build_query($query);
    }
}
