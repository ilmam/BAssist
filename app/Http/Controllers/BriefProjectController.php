<?php

namespace App\Http\Controllers;

use App\Models\Brief;
use App\Models\Project;
use App\Services\BriefProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Which projects came from a brief, and which part of the ask each takes.
 * The link is optional in both directions: a project may start without a brief
 * and be linked to one later, and a brief may never get a project (a dead end).
 * Rules live in BriefProjectService. See docs/brief-entity.md.
 */
class BriefProjectController extends Controller
{
    public function __construct(protected BriefProjectService $links)
    {
    }

    /** Link from the brief page. */
    public function storeFromBrief(Request $request, Brief $brief): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer'],
            'scope_note' => ['nullable', 'string'],
        ]);

        $this->links->link($brief, $this->findProject($validated['project_id']), $validated['scope_note'] ?? null);

        return back();
    }

    /** Link from the project page (a project that started without a brief). */
    public function storeFromProject(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'brief_id' => ['required', 'integer'],
            'scope_note' => ['nullable', 'string'],
        ]);

        $brief = Brief::query()->find($validated['brief_id']);
        if ($brief === null) {
            throw ValidationException::withMessages(['brief_id' => __('validation.exists', ['attribute' => 'brief'])]);
        }

        $this->links->link($brief, $project, $validated['scope_note'] ?? null);

        return back();
    }

    public function update(Request $request, Brief $brief, Project $project): RedirectResponse
    {
        $validated = $request->validate(['scope_note' => ['nullable', 'string']]);

        $this->links->updateNote($brief, $project, $validated['scope_note'] ?? null);

        return back();
    }

    public function destroy(Brief $brief, Project $project): RedirectResponse
    {
        $this->links->unlink($brief, $project);

        return back();
    }

    /** Start a project from a brief (any brief that is not declined). */
    public function createProject(Request $request, Brief $brief): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'scope_note' => ['nullable', 'string'],
        ]);

        $project = $this->links->createProject($brief, $validated['name'], $validated['code'] ?? null, $validated['scope_note'] ?? null);

        return redirect()->route('projects.dashboard', $project);
    }

    protected function findProject(int|string $id): Project
    {
        $project = Project::query()->find((int) $id);

        if ($project === null) {
            throw ValidationException::withMessages(['project_id' => __('validation.exists', ['attribute' => 'project'])]);
        }

        return $project;
    }
}
