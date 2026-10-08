<?php

namespace App\Http\Controllers;

use App\Repositories\ScreenRepository;
use App\Services\SaltScreenAssembler;
use App\Support\ScreenElementKind;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Serves a screen's assembled Salt; the browser draws it (docs/design-layer.md).
 */
class ScreenMockupController extends Controller
{
    public function show(int $id): View
    {
        $screen = app(ScreenRepository::class)->getById($id);

        return view('pages.screens.mockup', [
            'screen' => $screen,
            'salt' => $screen->salt,
        ]);
    }

    /**
     * Salt for rows that are not saved yet: the edit form's live preview. Writes nothing.
     */
    public function preview(Request $request, SaltScreenAssembler $assembler): JsonResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'elements' => ['nullable', 'array'],
            'elements.*.key' => ['nullable', 'string', 'max:64'],
            'elements.*.parent_key' => ['nullable', 'string', 'max:64'],
            'elements.*.row' => ['nullable', 'integer', 'min:0'],
            'elements.*.kind' => ['nullable', 'string', Rule::in(['', ...ScreenElementKind::values()])],
            'elements.*.label' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json([
            'salt' => $assembler->assemble(
                (string) ($data['title'] ?? ''),
                $assembler->normalizeRows($data['elements'] ?? []),
            ),
        ]);
    }
}
