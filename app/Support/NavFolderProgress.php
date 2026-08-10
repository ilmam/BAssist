<?php

namespace App\Support;

use App\Models\Architecture;
use App\Models\Assumption;
use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\BusinessRule;
use App\Models\ChangeRequest;
use App\Models\Constraint;
use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\NonFunctionalRequirement;
use App\Models\Project;
use App\Models\Risk;
use App\Models\ScopeItem;
use App\Models\Stakeholder;
use App\Models\StakeholderNeed;
use App\Models\StateFlow;
use App\Models\Status;
use App\Models\StrategicBaseline;
use App\Models\SwimlaneFlow;
use App\Support\ChangeRequestStatus;
use App\Support\StrategicBaselineStatus;

/**
 * Folder progress badges for project navigation (guide, never lock).
 *
 * A leaf is "ready" when it has meaningful project content / agreement.
 * Badge text: "{ready}/{total}" with an Agreed-oriented title.
 */
class NavFolderProgress
{
    protected ?int $agreedStatusId = null;

    /**
     * @param  array{
     *   key?: string,
     *   children: list<array<string, mixed>>
     * }  $folder
     * @param  list<array<string, mixed>>  $visibleChildren
     * @return array{ready: int, total: int, label: string, title: string}|null
     */
    public function forFolder(Project $project, array $folder, array $visibleChildren): ?array
    {
        $total = count($visibleChildren);
        if ($total === 0) {
            return null;
        }

        $ready = 0;
        foreach ($visibleChildren as $child) {
            $definition = $this->definitionForChild($folder['children'], $child);
            if ($definition !== null && $this->leafIsReady($project, $definition)) {
                $ready++;
            }
        }

        return [
            'ready' => $ready,
            'total' => $total,
            'label' => $ready.'/'.$total,
            'title' => __('ui.nav_folder_badge_title', [
                'ready' => $ready,
                'total' => $total,
                'folder' => $folder['short'] ?? ($folder['label'] ?? ''),
            ]),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $definitions
     * @param  array<string, mixed>  $child
     * @return array<string, mixed>|null
     */
    protected function definitionForChild(array $definitions, array $child): ?array
    {
        foreach ($definitions as $definition) {
            if (isset($definition['entity'], $child['entity']) && $definition['entity'] === $child['entity']) {
                return $definition;
            }
            if (
                isset($definition['route'], $child['route'])
                && $definition['route'] === $child['route']
            ) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function leafIsReady(Project $project, array $definition): bool
    {
        $progress = $definition['progress'] ?? 'entity_present';

        return match ($progress) {
            'entity_agreed' => $this->entityAgreed($project, (string) ($definition['entity'] ?? '')),
            'entity_present' => $this->entityPresent($project, (string) ($definition['entity'] ?? '')),
            'strategic_baseline' => $this->strategicBaselineReady($project),
            'guardrails_hub' => $this->guardrailsHubReady($project),
            'solution_hub' => $this->solutionHubReady($project),
            'diagrams_hub' => $this->diagramsHubReady($project),
            'change_requests_hub' => $this->changeRequestsHubReady($project),
            'traceability_hub' => $this->traceabilityHubReady($project),
            'acceptance_hub' => $this->acceptanceHubReady($project),
            default => false,
        };
    }

    protected function entityPresent(Project $project, string $entity): bool
    {
        return match ($entity) {
            'BusinessObjective' => BusinessObjective::query()->where('project_id', $project->id)->exists(),
            'BusinessNeed' => BusinessNeed::query()->where('project_id', $project->id)->exists(),
            'Stakeholder' => Stakeholder::query()
                ->where('project_id', $project->id)
                ->where(function ($q): void {
                    $q->where('is_system', false)
                        ->orWhereHas('stakeholderNeeds');
                })
                ->exists(),
            'StakeholderNeed' => StakeholderNeed::query()->where('project_id', $project->id)->exists(),
            'Risk' => Risk::query()->where('project_id', $project->id)->exists(),
            'ScopeItem' => ScopeItem::query()->where('project_id', $project->id)->exists(),
            'Assumption' => Assumption::query()->where('project_id', $project->id)->exists(),
            'Constraint' => Constraint::query()->where('project_id', $project->id)->exists(),
            'BusinessRule' => BusinessRule::query()->where('project_id', $project->id)->exists(),
            default => false,
        };
    }

    protected function entityAgreed(Project $project, string $entity): bool
    {
        $agreedId = $this->agreedStatusId();
        if ($agreedId === null) {
            return $this->entityPresent($project, $entity);
        }

        return match ($entity) {
            'BusinessObjective' => BusinessObjective::query()
                ->where('project_id', $project->id)
                ->where('status_id', $agreedId)
                ->exists(),
            'BusinessNeed' => BusinessNeed::query()
                ->where('project_id', $project->id)
                ->where('status_id', $agreedId)
                ->exists(),
            'StakeholderNeed' => StakeholderNeed::query()
                ->where('project_id', $project->id)
                ->where('status_id', $agreedId)
                ->exists(),
            'Stakeholder' => $this->entityPresent($project, 'Stakeholder'),
            default => false,
        };
    }

    protected function strategicBaselineReady(Project $project): bool
    {
        $baseline = StrategicBaseline::query()->where('project_id', $project->id)->first();

        return $baseline !== null
            && (
                $baseline->status === StrategicBaselineStatus::APPROVED
                || $baseline->hasStrategyContent()
            );
    }

    protected function guardrailsHubReady(Project $project): bool
    {
        return Assumption::query()->where('project_id', $project->id)->exists()
            || Constraint::query()->where('project_id', $project->id)->exists()
            || BusinessRule::query()->where('project_id', $project->id)->exists();
    }

    protected function solutionHubReady(Project $project): bool
    {
        $agreedId = $this->agreedStatusId();

        $fr = FunctionalRequirement::query()->where('project_id', $project->id);
        $nfr = NonFunctionalRequirement::query()->where('project_id', $project->id);
        $features = Feature::query()->where('project_id', $project->id);

        if ($agreedId !== null) {
            return (clone $fr)->where('status_id', $agreedId)->exists()
                || (clone $nfr)->where('status_id', $agreedId)->exists()
                || (clone $features)->where('status_id', $agreedId)->exists();
        }

        return $fr->exists() || $nfr->exists() || $features->exists();
    }

    protected function diagramsHubReady(Project $project): bool
    {
        return Architecture::query()->where('project_id', $project->id)->exists()
            || StateFlow::query()->where('project_id', $project->id)->exists()
            || SwimlaneFlow::query()->where('project_id', $project->id)->exists();
    }

    protected function changeRequestsHubReady(Project $project): bool
    {
        $openDrafts = ChangeRequest::query()
            ->where('project_id', $project->id)
            ->where('status', ChangeRequestStatus::DRAFT)
            ->exists();

        // Ready when there are no unresolved drafts (including zero CRs).
        return ! $openDrafts;
    }

    protected function traceabilityHubReady(Project $project): bool
    {
        return BusinessObjective::query()->where('project_id', $project->id)->exists()
            && BusinessNeed::query()->where('project_id', $project->id)->exists()
            && StakeholderNeed::query()->where('project_id', $project->id)->exists();
    }

    protected function acceptanceHubReady(Project $project): bool
    {
        return Feature::query()
            ->where('project_id', $project->id)
            ->whereHas('scenarios')
            ->exists()
            || FunctionalRequirement::query()
                ->where('project_id', $project->id)
                ->whereNotNull('acceptance_criteria')
                ->where('acceptance_criteria', '!=', '')
                ->exists()
            || NonFunctionalRequirement::query()
                ->where('project_id', $project->id)
                ->whereNotNull('acceptance_criteria')
                ->where('acceptance_criteria', '!=', '')
                ->exists();
    }

    protected function agreedStatusId(): ?int
    {
        if ($this->agreedStatusId !== null) {
            return $this->agreedStatusId;
        }

        $id = Status::query()->where('code', EntityStatus::AGREED)->value('id');
        $this->agreedStatusId = $id !== null ? (int) $id : null;

        return $this->agreedStatusId;
    }
}
