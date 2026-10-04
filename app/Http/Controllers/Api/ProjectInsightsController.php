<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Project;
use App\Services\AcceptancePlanBuilder;
use App\Services\GherkinFeatureAssembler;
use App\Services\ProjectReadinessService;
use App\Services\TraceabilityGraphService;
use App\Services\TraceabilityMatrixService;
use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
use App\Support\ProjectContext;
use App\Support\Tenancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only JSON for the derived, project-level views that the web UI already
 * shows: readiness, traceability, acceptance plan and assembled Gherkin.
 *
 * Nothing is computed here. Each action authorizes exactly like its web
 * counterpart, pins the project, calls the same service the page calls and
 * returns the result. See docs/api-platform.md.
 */
class ProjectInsightsController extends Controller
{
    /**
     * Gap summary and spine progress (same data as the project dashboard panel).
     */
    public function readiness(Project $project, ProjectReadinessService $readiness): JsonResponse
    {
        EntityAccess::authorize(auth()->user(), 'Project', EntityAccess::VIEW);
        $this->pin($project);

        return response()->json(
            ['project' => $this->projectSummary($project)] + $readiness->forProject($project)
        );
    }

    /**
     * Traceability matrix rows, gap counts and coverage for one project.
     * Query: orphans_only=1, gap={gap key}.
     */
    public function traceability(
        Request $request,
        Project $project,
        TraceabilityMatrixService $matrix,
        TraceabilityGraphService $graph,
    ): JsonResponse {
        $this->authorizeAnyView(['BusinessNeed', 'BusinessObjective', 'StakeholderNeed', 'Feature', 'FunctionalRequirement']);
        $this->pin($project);

        $result = $matrix->build(
            ['project_id' => $project->id] + $request->only(['orphans_only', 'gap'])
        );

        return response()->json([
            'project' => $this->projectSummary($project),
            'summary' => $result['summary'],
            'gap_counts' => $result['gap_counts'] ?? [],
            'coverage' => $graph->coverage($result['rows']),
            'rows' => $result['rows'],
        ]);
    }

    /**
     * Acceptance checks (BDD scenarios plus FR/NFR acceptance criteria).
     * Query: feature_id, stakeholder_need_id, type.
     */
    public function acceptancePlan(Request $request, Project $project, AcceptancePlanBuilder $builder): JsonResponse
    {
        $this->authorizeAnyView(['Feature', 'Scenario', 'FunctionalRequirement']);
        $this->pin($project);

        $plan = $builder->build(
            ['project_id' => $project->id] + $request->only(['feature_id', 'stakeholder_need_id', 'type'])
        );

        return response()->json([
            'project' => $this->projectSummary($project),
            'summary' => $plan['summary'],
            'rows' => $plan['rows'],
        ]);
    }

    /**
     * Every feature of the project as an assembled .feature document.
     */
    public function gherkin(Project $project, GherkinFeatureAssembler $assembler): JsonResponse
    {
        EntityAccess::authorize(auth()->user(), 'Feature', EntityAccess::VIEW);
        $this->pin($project);

        $repository = CrudEntityRegistry::repository('Feature');

        $features = Feature::query()
            ->where('project_id', $project->id)
            ->orderBy('number')
            ->pluck('id')
            ->map(fn ($id) => FeatureGherkinController::document($repository->findForDocument((int) $id), $assembler))
            ->all();

        return response()->json([
            'project' => $this->projectSummary($project),
            'features' => $features,
        ]);
    }

    /**
     * Route-model binding is already tenant-scoped; assertProject is the same
     * second check the web dashboard makes. Pinning the project keeps the
     * services' workspace/project filters on this project for token requests,
     * which carry no sticky session context.
     */
    protected function pin(Project $project): void
    {
        Tenancy::assertProject($project);
        app(ProjectContext::class)->set((int) $project->id);
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
    protected function projectSummary(Project $project): array
    {
        return [
            'id' => (int) $project->id,
            'code' => $project->code,
            'name' => $project->name,
            'workspace_id' => (int) $project->workspace_id,
        ];
    }
}
