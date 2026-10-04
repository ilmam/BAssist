<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProjectInsightsService;
use Illuminate\Http\JsonResponse;

/**
 * Where one record sits on the Need Spine: its parents, children, local gaps
 * and the five-level lineage rail (same data as the record's detail page).
 *
 * Available for the entities SpineCascadeService understands; any other
 * entity answers 404.
 */
class LineageController extends Controller
{
    public function show(string $resource, int $id, ProjectInsightsService $insights): JsonResponse
    {
        return response()->json($insights->lineage($resource, $id));
    }
}
