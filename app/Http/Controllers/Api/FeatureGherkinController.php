<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProjectInsightsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * One feature as an assembled .feature document (the web "Export" button).
 * JSON by default; ?format=text returns the raw Gherkin.
 */
class FeatureGherkinController extends Controller
{
    public function show(Request $request, int $id, ProjectInsightsService $insights): JsonResponse|Response
    {
        $document = $insights->featureGherkin($id);

        if ($request->query('format') === 'text') {
            return response($document['gherkin'], 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return response()->json($document);
    }
}
