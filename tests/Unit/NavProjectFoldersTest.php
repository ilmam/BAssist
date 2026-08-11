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
            ['BusinessNeed', 'BusinessObjective', 'Risk', 'strategic_baselines.for-project', 'ScopeItem'],
            $this->childKeys($folders['strategy']['children'])
        );
        $this->assertSame(
            [
                'Stakeholder',
                'StakeholderNeed',
                'guardrails.index',
                'solution_requirements.index',
                'diagrams.index',
            ],
            $this->childKeys($folders['radd']['children'])
        );

        $guardrails = collect($folders['radd']['children'])
            ->first(fn (array $child): bool => ($child['route'] ?? null) === 'guardrails.index');
        $this->assertIsArray($guardrails);
        $this->assertSame('Rules & Assumptions', $guardrails['label'] ?? null);
        $this->assertEqualsCanonicalizing(
            ['Assumption', 'Constraint', 'BusinessRule'],
            $guardrails['entities'] ?? []
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

    public function test_diagrams_and_guardrails_hubs_are_kept_while_strategy_hub_is_removed(): void
    {
        $routes = collect(config('navigation.hierarchy.project_folders'))
            ->flatMap(fn ($folder) => $folder['children'] ?? [])
            ->pluck('route')
            ->filter()
            ->values()
            ->all();

        $this->assertContains('diagrams.index', $routes);
        $this->assertContains('guardrails.index', $routes);
        $this->assertNotContains('strategy.index', $routes);
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
        $this->assertStringNotContainsString('phase_index', $builder);
        $this->assertStringNotContainsString('nav-phase-folder__index', $menu);
        $this->assertStringContainsString('nav-folder-badge', $menu);
        $this->assertStringContainsString('nav-phase-folder', $menu);
        $this->assertStringContainsString('nav-project-icon', $menu);
        $this->assertStringContainsString("'type' => 'project'", $builder);
        $this->assertStringContainsString('project_icon_img', $builder);
        $this->assertSame('images/ba-logo.png', config('navigation.hierarchy.project_icon_img'));
        $this->assertFileExists(dirname(__DIR__, 2).'/public/images/ba-logo.png');
        $this->assertStringContainsString('data-folder-key', $menu);
        $this->assertStringContainsString('guide, never lock', $progress);
        $this->assertNotSame('ui.nav_folder_badge_title', __('ui.nav_folder_badge_title'));

        // Temporarily disabled — flip config to re-enable badges without code changes.
        $this->assertFalse(config('navigation.hierarchy.show_folder_badges'));
    }

    public function test_phase_folders_have_distinct_badge_tones(): void
    {
        $folders = collect(config('navigation.hierarchy.project_folders'))->keyBy('key');

        $this->assertSame('strategy', $folders['strategy']['badge_tone'] ?? null);
        $this->assertSame('radd', $folders['radd']['badge_tone'] ?? null);
        $this->assertSame('governance', $folders['governance']['badge_tone'] ?? null);
        $this->assertSame('evaluation', $folders['evaluation']['badge_tone'] ?? null);
    }

    public function test_all_projects_and_all_workspaces_nav_links_are_removed(): void
    {
        $builder = file_get_contents(dirname(__DIR__, 2).'/app/Support/NavTreeBuilder.php');

        $this->assertIsString($builder);
        $this->assertArrayNotHasKey('all_projects_label', config('navigation.hierarchy'));
        $this->assertArrayNotHasKey('all_workspaces_label', config('navigation.hierarchy'));
        $this->assertStringNotContainsString('all_projects_label', $builder);
        $this->assertStringNotContainsString('all_workspaces_label', $builder);
        $this->assertStringNotContainsString('All Projects', $builder);
        $this->assertStringNotContainsString('All Workspaces', $builder);
        // Workspace row remains the entry point to the project list.
        $this->assertStringContainsString("model_route_name('Project', 'index')", $builder);
        $this->assertStringContainsString("'workspace_id' => \$workspace->id", $builder);
        // Root "Workspaces" label opens the workspace list.
        $this->assertStringContainsString("model_route_name('Workspace', 'index')", $builder);
        $this->assertStringContainsString("'clear_workspace' => 1", $builder);
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
