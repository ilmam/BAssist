<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Services\GherkinFeatureAssembler;
use App\Support\CrudEntityRegistry;
use App\Support\EntityAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * One feature as an assembled .feature document (the web "Export" button).
 * JSON by default; ?format=text returns the raw Gherkin.
 */
class FeatureGherkinController extends Controller
{
    public function show(Request $request, int $id, GherkinFeatureAssembler $assembler): JsonResponse|Response
    {
        EntityAccess::authorize(auth()->user(), 'Feature', EntityAccess::VIEW);

        $feature = CrudEntityRegistry::repository('Feature')->findForDocument($id);
        $document = self::document($feature, $assembler);

        if ($request->query('format') === 'text') {
            return response($document['gherkin'], 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return response()->json($document);
    }

    /**
     * @return array{id: int, code: string|null, title: string|null, stakeholder_need_id: int|null, scenarios_count: int, filename: string, gherkin: string}
     */
    public static function document(Feature $feature, GherkinFeatureAssembler $assembler): array
    {
        return [
            'id' => (int) $feature->id,
            'code' => $feature->getAttribute('code'),
            'title' => $feature->title,
            'stakeholder_need_id' => $feature->stakeholder_need_id !== null ? (int) $feature->stakeholder_need_id : null,
            'scenarios_count' => $feature->scenarios->count(),
            'filename' => $assembler->downloadFilename($feature),
            'gherkin' => $assembler->assembleFeature($feature),
        ];
    }
}
