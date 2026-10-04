<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ProjectInsightsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only JSON for the derived, project-level views that the web UI already
 * shows: readiness, traceability, acceptance plan and assembled Gherkin.
 *
 * Authorization and the data itself live in ProjectInsightsService, which the
 * MCP tools also call. See docs/api-platform.md.
 */
class ProjectInsightsController extends Controller
{
    public function __construct(protected ProjectInsightsService $insights)
    {
    }

    public function readiness(Project $project): JsonResponse
    {
        return response()->json($this->insights->readiness($project));
    }

    /** Query: orphans_only=1, gap={gap key}. */
    public function traceability(Request $request, Project $project): JsonResponse
    {
        return response()->json(
            $this->insights->traceability($project, $request->only(['orphans_only', 'gap']))
        );
    }

    /** Query: feature_id, stakeholder_need_id, type. */
    public function acceptancePlan(Request $request, Project $project): JsonResponse
    {
        return response()->json(
            $this->insights->acceptancePlan($project, $request->only(['feature_id', 'stakeholder_need_id', 'type']))
        );
    }

    public function gherkin(Project $project): JsonResponse
    {
        return response()->json($this->insights->projectGherkin($project));
    }
}
