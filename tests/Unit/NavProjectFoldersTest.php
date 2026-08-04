<?php

namespace Tests\Unit;

use Tests\TestCase;

class NavProjectFoldersTest extends TestCase
{
    public function test_four_babok_folders_are_configured_in_journey_order(): void
    {
        $folders = config('navigation.hierarchy.project_folders');

        $this->assertIsArray($folders);
        $this->assertSame(
            ['strategy', 'radd', 'governance', 'evaluation'],
            array_column($folders, 'key')
        );
    }

    public function test_folder_children_match_requested_structure(): void
    {
        $folders = collect(config('navigation.hierarchy.project_folders'))->keyBy('key');

        $this->assertSame(
            ['BusinessObjective', 'BusinessNeed', 'Risk', 'strategic_baselines.for-project', 'ScopeItem'],
            $this->childKeys($folders['strategy']['children'])
        );
        $this->assertSame(
            [
                'Stakeholder',
                'StakeholderNeed',
                'solution_requirements.index',
                'Assumption',
                'Constraint',
                'BusinessRule',
                'diagrams.index',
            ],
            $this->childKeys($folders['radd']['children'])
        );
        $this->assertSame(
            ['change_requests.index', 'traceability.index'],
            $this->childKeys($folders['governance']['children'])
        );
        $this->assertSame(
            ['acceptance-plan.index'],
            $this->childKeys($folders['evaluation']['children'])
        );
    }

    public function test_spine_entities_are_not_flat_nav_leaves(): void
    {
        $entities = config('crud.models');

        foreach ([
            'BusinessObjective',
            'BusinessNeed',
            'Stakeholder',
            'StakeholderNeed',
            'ScopeItem',
            'Assumption',
            'Constraint',
            'BusinessRule',
            'StrategicBaseline',
        ] as $model) {
            $this->assertFalse($entities[$model]['nav'] ?? true, "{$model} should live under folders, not flat nav");
        }
    }

    public function test_diagrams_hub_is_kept_while_strategy_and_guardrails_hubs_are_removed(): void
    {
        $routes = collect(config('navigation.hierarchy.project_folders'))
            ->flatMap(fn ($folder) => $folder['children'] ?? [])
            ->pluck('route')
            ->filter()
            ->values()
            ->all();

        $this->assertContains('diagrams.index', $routes);
        $this->assertNotContains('strategy.index', $routes);
        $this->assertNotContains('guardrails.index', $routes);
        $this->assertContains('strategic_baselines.for-project', $routes);
    }

    public function test_nav_tree_builder_and_menu_support_folder_badges(): void
    {
        $builder = file_get_contents(dirname(__DIR__, 2).'/app/Support/NavTreeBuilder.php');
        $progress = file_get_contents(dirname(__DIR__, 2).'/app/Support/NavFolderProgress.php');
        $menu = file_get_contents(dirname(__DIR__, 2).'/resources/views/themes/metronic9/partials/menu-items.blade.php');

        $this->assertIsString($builder);
        $this->assertIsString($progress);
        $this->assertIsString($menu);
        $this->assertStringContainsString('projectFolderItems', $builder);
        $this->assertStringContainsString('NavFolderProgress', $builder);
        $this->assertStringContainsString('show_folder_badges', $builder);
        $this->assertStringContainsString('nav-folder-badge', $menu);
        $this->assertStringContainsString('guide, never lock', $progress);
        $this->assertNotSame('ui.nav_folder_badge_title', __('ui.nav_folder_badge_title'));

        // Temporarily disabled — flip config to re-enable badges without code changes.
        $this->assertFalse(config('navigation.hierarchy.show_folder_badges'));
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @return list<string>
     */
    protected function childKeys(array $children): array
    {
        return array_map(function (array $child): string {
            return $child['entity'] ?? $child['route'] ?? '';
        }, $children);
    }
}
