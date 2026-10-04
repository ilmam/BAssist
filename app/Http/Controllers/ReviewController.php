<?php

namespace App\Http\Controllers;

use App\Services\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Approve / Request changes (#8). Returns JSON; the page or modal reloads to show the
 * new status, the review bar, the posted comment and the history entry together.
 */
class ReviewController extends Controller
{
    public function __construct(protected ApprovalService $approvals) {}

    public function approve(Request $request, string $model, int $id): JsonResponse
    {
        $record = $this->approvals->record(Str::studly($model), $id);
        $validated = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $this->approvals->approve($record, $validated['note'] ?? null);

        return response()->json(['ok' => true, 'message' => __('ui.review_approved_toast')]);
    }

    public function requestChanges(Request $request, string $model, int $id): JsonResponse
    {
        $record = $this->approvals->record(Str::studly($model), $id);
        $validated = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $this->approvals->requestChanges($record, $validated['note']);

        return response()->json(['ok' => true, 'message' => __('ui.review_changes_toast')]);
    }
}
