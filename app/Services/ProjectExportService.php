<?php

namespace App\Services;

use App\Models\Architecture;
use App\Models\Project;
use App\Support\ScopeItemDirection;

/**
 * Assembles a printable project artifact pack (HTML / browser print-to-PDF).
 */
class ProjectExportService
{
    public function __construct(
        protected TraceabilityMatrixService $matrix,
        protected StateDiagramMermaidGenerator $stateDiagrams,
        protected SwimlaneMermaidGenerator $swimlanes,
        protected C4MermaidGenerator $c4,
        protected ProjectReadinessService $readiness,
        protected GherkinFeatureAssembler $gherkin,
    ) {
    }

    /**
     * @return array{
     *   project: Project,
     *   generated_at: \Illuminate\Support\Carbon,
     *   readiness: array{total_gaps: int, items: list<array{key: string, label: string, count: int, severity: string, url: string|null}>},
     *   strategic_baseline: \App\Models\StrategicBaseline|null,
     *   scope_items: \Illuminate\Database\Eloquent\Collection,
     *   objectives: \Illuminate\Database\Eloquent\Collection,
     *   needs: \Illuminate\Database\Eloquent\Collection,
     *   stakeholders: \Illuminate\Database\Eloquent\Collection,
     *   stakeholder_needs: \Illuminate\Database\Eloquent\Collection,
     *   state_flows: list<array{model: \App\Models\StateFlow, mermaid: string}>,
     *   swimlane_flows: list<array{model: \App\Models\SwimlaneFlow, mermaid: string}>,
     *   architecture: array{model: Architecture, views: list<array{level: string, title: string, mermaid: string}>}|null,
     *   assumptions: \Illuminate\Database\Eloquent\Collection,
     *   constraints: \Illuminate\Database\Eloquent\Collection,
     *   business_rules: \Illuminate\Database\Eloquent\Collection,
     *   risks: \Illuminate\Database\Eloquent\Collection,
     *   features: list<array{model: \App\Models\Feature, gherkin: string}>,
     *   functional_requirements: \Illuminate\Database\Eloquent\Collection,
     *   matrix: array{rows: list<array<string, mixed>>, summary: array<string, int>}
     * }
     */
    public function build(Project $project): array
    {
        $project->loadMissing([
            'workspace',
            'status',
            'businessObjectives.priority',
            'businessObjectives.status',
            'businessNeeds.priority',
            'businessNeeds.status',
            'businessNeeds.businessObjectives',
            // Export stakeholders matrix: only rows linked to ≥1 StakeholderNeed (project "requirements").
            'stakeholders.stakeholderNeeds',
            'stakeholderNeeds.priority',
            'stakeholderNeeds.status',
            'stakeholderNeeds.stakeholders',
            'stakeholderNeeds.businessNeeds',
            'stateFlows.status',
            'swimlaneFlows.status',
            'architecture.status',
            'assumptions',
            'constraints',
            'businessRules',
            'strategicBaseline',
            'scopeItems',
            'risks',
            'functionalRequirements.priority',
            'functionalRequirements.status',
            'functionalRequirements.stakeholderNeed',
            'features.priority',
            'features.status',
            'features.stakeholderNeed',
            'features.scenarios',
        ]);

        $matrix = $this->matrix->build(['project_id' => $project->id]);

        // Stakeholders section rule: include only stakeholders that have at least one
        // related StakeholderNeed (pivot). Unlinked role seeds / profiles are omitted.
        $stakeholdersWithNeeds = $project->stakeholders
            ->filter(fn ($stakeholder) => $stakeholder->stakeholderNeeds->isNotEmpty())
            ->sortBy('name')
            ->values();

        return [
            'project' => $project,
            'generated_at' => now(),
            'readiness' => $this->readiness->forProject($project),
            // Strategy early in the pack (blade order: after readiness, before BO/BN).
            'strategic_baseline' => $project->strategicBaseline,
            'scope_items' => $project->scopeItems
                ->sortBy([
                    fn ($item) => $item->direction === ScopeItemDirection::IN ? 0 : 1,
                    'title',
                ])
                ->values(),
            'objectives' => $project->businessObjectives->sortBy('number')->values(),
            'needs' => $project->businessNeeds->sortBy('number')->values(),
            'stakeholders' => $stakeholdersWithNeeds,
            'stakeholder_needs' => $project->stakeholderNeeds->sortBy('number')->values(),
            'state_flows' => $project->stateFlows
                ->sortBy('title')
                ->values()
                ->map(fn ($flow) => [
                    'model' => $flow,
                    'mermaid' => $this->stateDiagrams->generate(
                        $flow->title,
                        $flow->normalizedTransitions()
                    ),
                ])
                ->all(),
            'swimlane_flows' => $project->swimlaneFlows
                ->sortBy('title')
                ->values()
                ->map(fn ($flow) => [
                    'model' => $flow,
                    'mermaid' => $this->swimlanes->generate(
                        $flow->title,
                        $flow->normalizedElements(),
                        (string) ($flow->direction ?? 'TB')
                    ),
                ])
                ->all(),
            'architecture' => $this->buildArchitectureExport($project->architecture),
            'assumptions' => $project->assumptions->sortBy('title')->values(),
            'constraints' => $project->constraints->sortBy('title')->values(),
            'business_rules' => $project->businessRules->sortBy('title')->values(),
            'risks' => $project->risks->sortBy('number')->values(),
            'functional_requirements' => $project->functionalRequirements->sortBy('number')->values(),
            // Features last among artifacts (before the matrix appendix).
            'features' => $project->features
                ->sortBy('number')
                ->values()
                ->map(fn ($feature) => [
                    'model' => $feature,
                    'gherkin' => $this->gherkin->assembleFeature($feature),
                ])
                ->all(),
            'matrix' => [
                'rows' => $matrix['rows'],
                'summary' => $matrix['summary'],
            ],
        ];
    }

    /**
     * Build printable C4 views only when the project has diagrammable architecture content.
     *
     * @return array{model: Architecture, views: list<array{level: string, title: string, mermaid: string}>}|null
     */
    public function buildArchitectureExport(?Architecture $architecture): ?array
    {
        if ($architecture === null) {
            return null;
        }

        $elements = $architecture->normalizedElements();
        $hasDiagrammable = collect($elements)->contains(
            static fn (array $el): bool => in_array($el['kind'] ?? '', ['person', 'system', 'container', 'component'], true)
        );

        if (! $hasDiagrammable) {
            return null;
        }

        $relationships = $architecture->normalizedRelationships();
        $layout = $architecture->normalizedLayout();
        $views = [];

        $hasContextNodes = collect($elements)->contains(
            static fn (array $el): bool => in_array($el['kind'] ?? '', ['person', 'system'], true)
        );

        if ($hasContextNodes) {
            $views[] = [
                'level' => 'context',
                'title' => __('ui.c4_level_context'),
                'mermaid' => $this->c4->toContext($elements, $relationships, $layout),
            ];
        }

        $systems = array_values(array_filter(
            $elements,
            static fn (array $el): bool => ($el['kind'] ?? '') === 'system' && empty($el['external'])
        ));

        foreach ($systems as $system) {
            $containers = array_values(array_filter(
                $elements,
                static fn (array $el): bool => ($el['kind'] ?? '') === 'container'
                    && ($el['parent_key'] ?? null) === $system['key']
            ));
            if ($containers === []) {
                continue;
            }

            $views[] = [
                'level' => 'container',
                'title' => __('ui.c4_level_container').' — '.$system['name'],
                'mermaid' => $this->c4->toContainer($elements, $relationships, $system['key'], $layout),
            ];
        }

        $containers = array_values(array_filter(
            $elements,
            static fn (array $el): bool => ($el['kind'] ?? '') === 'container'
        ));

        foreach ($containers as $container) {
            $components = array_values(array_filter(
                $elements,
                static fn (array $el): bool => ($el['kind'] ?? '') === 'component'
                    && ($el['parent_key'] ?? null) === $container['key']
            ));
            if ($components === []) {
                continue;
            }

            $views[] = [
                'level' => 'component',
                'title' => __('ui.c4_level_component').' — '.$container['name'],
                'mermaid' => $this->c4->toComponent($elements, $relationships, $container['key'], $layout),
            ];
        }

        if ($views === []) {
            return null;
        }

        return [
            'model' => $architecture,
            'views' => $views,
        ];
    }
}
