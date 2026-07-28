<?php

namespace App\Services;

use App\Models\Project;

/**
 * Assembles a printable project artifact pack (HTML / browser print-to-PDF).
 */
class ProjectExportService
{
    public function __construct(
        protected TraceabilityMatrixService $matrix,
        protected StateDiagramMermaidGenerator $stateDiagrams,
        protected SwimlaneMermaidGenerator $swimlanes,
        protected ProjectReadinessService $readiness,
    ) {
    }

    /**
     * @return array{
     *   project: Project,
     *   generated_at: \Illuminate\Support\Carbon,
     *   readiness: array{total_gaps: int, items: list<array{key: string, label: string, count: int, severity: string, url: string|null}>},
     *   objectives: \Illuminate\Database\Eloquent\Collection,
     *   needs: \Illuminate\Database\Eloquent\Collection,
     *   stakeholders: \Illuminate\Database\Eloquent\Collection,
     *   stakeholder_needs: \Illuminate\Database\Eloquent\Collection,
     *   state_flows: list<array{model: \App\Models\StateFlow, mermaid: string}>,
     *   swimlane_flows: list<array{model: \App\Models\SwimlaneFlow, mermaid: string}>,
     *   assumptions: \Illuminate\Database\Eloquent\Collection,
     *   constraints: \Illuminate\Database\Eloquent\Collection,
     *   business_rules: \Illuminate\Database\Eloquent\Collection,
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
            'assumptions',
            'constraints',
            'businessRules',
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
            'assumptions' => $project->assumptions->sortBy('title')->values(),
            'constraints' => $project->constraints->sortBy('title')->values(),
            'business_rules' => $project->businessRules->sortBy('title')->values(),
            'matrix' => [
                'rows' => $matrix['rows'],
                'summary' => $matrix['summary'],
            ],
        ];
    }
}
