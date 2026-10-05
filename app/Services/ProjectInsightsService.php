<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\Project;
use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
use App\Support\ProjectContext;
use App\Support\Tenancy;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The derived, read-only views of a project, for callers outside the web UI
 * (JSON API controllers and MCP tools share this class, so they cannot differ).
 *
 * Nothing is computed here. Each method authorizes like the matching web page,
 * pins the project, calls the service that page uses and returns plain arrays.
 * See docs/api-platform.md.
 */
class ProjectInsightsService
{
    public function __construct(
        protected ProjectReadinessService $readiness,
        protected TraceabilityMatrixService $matrix,
        protected TraceabilityGraphService $graph,
        protected AcceptancePlanBuilder $acceptance,
        protected GherkinFeatureAssembler $assembler,
        protected SpineCascadeService $cascade,
        protected CommentService $comments,
        protected ProjectContext $projectContext,
    ) {
    }

    /**
     * Find a project by id inside the caller's tenant, or fail with 404.
     */
    public function project(int $id): Project
    {
        // TenantScope confines the lookup; a foreign id is simply not found.
        return Project::query()->findOrFail($id);
    }

    /**
     * Gap summary and spine progress (the project dashboard panel).
     *
     * @return array<string, mixed>
     */
    public function readiness(Project $project): array
    {
        EntityAccess::authorize(auth()->user(), 'Project', EntityAccess::VIEW);
        $this->pin($project);

        return ['project' => $this->projectSummary($project)] + $this->readiness->forProject($project);
    }

    /**
     * @param  array{orphans_only?: mixed, gap?: mixed}  $filters
     * @return array<string, mixed>
     */
    public function traceability(Project $project, array $filters = []): array
    {
        $this->authorizeAnyView(['BusinessNeed', 'BusinessObjective', 'StakeholderNeed', 'Feature', 'FunctionalRequirement']);
        $this->pin($project);

        $result = $this->matrix->build(
            ['project_id' => $project->id] + array_intersect_key($filters, array_flip(['orphans_only', 'gap']))
        );

        return [
            'project' => $this->projectSummary($project),
            'summary' => $result['summary'],
            'gap_counts' => $result['gap_counts'] ?? [],
            'coverage' => $this->graph->coverage($result['rows']),
            'rows' => $result['rows'],
        ];
    }

    /**
     * @param  array{feature_id?: mixed, stakeholder_need_id?: mixed, type?: mixed}  $filters
     * @return array<string, mixed>
     */
    public function acceptancePlan(Project $project, array $filters = []): array
    {
        $this->authorizeAnyView(['Feature', 'Scenario', 'FunctionalRequirement']);
        $this->pin($project);

        $plan = $this->acceptance->build(
            ['project_id' => $project->id]
            + array_intersect_key($filters, array_flip(['feature_id', 'stakeholder_need_id', 'type']))
        );

        return [
            'project' => $this->projectSummary($project),
            'summary' => $plan['summary'],
            'rows' => $plan['rows'],
        ];
    }

    /**
     * Every feature of the project as an assembled .feature document.
     *
     * @return array<string, mixed>
     */
    public function projectGherkin(Project $project): array
    {
        EntityAccess::authorize(auth()->user(), 'Feature', EntityAccess::VIEW);
        $this->pin($project);

        $repository = CrudEntityRegistry::repository('Feature');

        return [
            'project' => $this->projectSummary($project),
            'features' => Feature::query()
                ->where('project_id', $project->id)
                ->orderBy('number')
                ->pluck('id')
                ->map(fn ($id) => $this->featureDocument($repository->findForDocument((int) $id)))
                ->all(),
        ];
    }

    /**
     * One feature as an assembled .feature document.
     *
     * @return array{id: int, code: string|null, title: string|null, stakeholder_need_id: int|null, scenarios_count: int, filename: string, gherkin: string}
     */
    public function featureGherkin(int $featureId): array
    {
        EntityAccess::authorize(auth()->user(), 'Feature', EntityAccess::VIEW);

        return $this->featureDocument(CrudEntityRegistry::repository('Feature')->findForDocument($featureId));
    }

    /**
     * Where one record sits on the Need Spine: parents, children, local gaps
     * and the five-level lineage rail. 404 for entities with no place on it.
     *
     * @param  string  $entity  model name (Feature) or resource name (features)
     * @return array<string, mixed>
     */
    public function lineage(string $entity, int $id): array
    {
        $model = CrudEntityRegistry::modelFromResource($entity)
            ?? (array_key_exists($entity, CrudEntityRegistry::all()) ? $entity : null);

        if ($model === null) {
            throw new NotFoundHttpException('Unknown entity.');
        }

        EntityAccess::authorize(auth()->user(), $model, EntityAccess::VIEW);

        // Tenant isolation: the repository lookup inside runs under TenantScope.
        $result = $this->cascade->for($model, $id);
        if ($result === null) {
            throw new NotFoundHttpException('Lineage is not available for this entity.');
        }

        // Comment threads on the record, by status. open_comments counts the ones
        // that block its build gate: open (no answer) and answered (not yet
        // applied). Implemented threads only wait for a person to verify.
        $comments = CommentService::supports($model)
            ? $this->comments->statusCounts(null, CrudEntityRegistry::repository($model)->findModel($id))
            : ['open' => 0, 'answered' => 0, 'implemented' => 0];

        return [
            'entity' => $model,
            'id' => $id,
            'open_comments' => $comments['open'] + $comments['answered'],
            'comments' => $comments,
        ] + $result;
    }

    /**
     * Route-model binding and project() are already tenant-scoped; assertProject
     * is the same second check the web dashboard makes. Pinning keeps the
     * services' workspace/project filters on this project for token requests,
     * which carry no sticky session context.
     */
    protected function pin(Project $project): void
    {
        Tenancy::assertProject($project);
        $this->projectContext->set((int) $project->id);
    }

    /**
     * @param  list<string>  $entities
     */
    protected function authorizeAnyView(array $entities): void
    {
        $user = auth()->user();

        foreach ($entities as $entity) {
            if (EntityAccess::can($user, $entity, EntityAccess::VIEW)) {
                return;
            }
        }

        EntityAccess::authorize($user, $entities[0], EntityAccess::VIEW);
    }

    /**
     * @return array{id: int, code: string|null, name: string|null, workspace_id: int}
     */
    public function projectSummary(Project $project): array
    {
        return [
            'id' => (int) $project->id,
            'code' => $project->code,
            'name' => $project->name,
            'workspace_id' => (int) $project->workspace_id,
        ];
    }

    /**
     * @return array{id: int, code: string|null, title: string|null, stakeholder_need_id: int|null, scenarios_count: int, filename: string, gherkin: string}
     */
    protected function featureDocument(Feature $feature): array
    {
        return [
            'id' => (int) $feature->id,
            'code' => $feature->getAttribute('code'),
            'title' => $feature->title,
            'stakeholder_need_id' => $feature->stakeholder_need_id !== null ? (int) $feature->stakeholder_need_id : null,
            'scenarios_count' => $feature->scenarios->count(),
            'filename' => $this->assembler->downloadFilename($feature),
            'gherkin' => $this->assembler->assembleFeature($feature),
        ];
    }
}
