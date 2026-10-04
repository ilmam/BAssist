<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SpineCascadeService;
use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
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
    public function show(string $resource, int $id, SpineCascadeService $cascade): JsonResponse
    {
        $model = CrudEntityRegistry::modelFromResource($resource);
        if ($model === null) {
            abort(404);
        }

        EntityAccess::authorize(auth()->user(), $model, EntityAccess::VIEW);

        // Tenant isolation: the repository lookup inside runs under TenantScope.
        $result = $cascade->for($model, $id);
        if ($result === null) {
            abort(404, 'Lineage is not available for this entity.');
        }

        return response()->json(['entity' => $model, 'id' => $id] + $result);
    }
}
