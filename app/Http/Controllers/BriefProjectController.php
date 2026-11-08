<?php

namespace App\Http\Controllers;

use App\Models\Brief;
use App\Models\Project;
use App\Support\EntityAccess;
use App\Support\ProjectAccess;
use App\Support\ProjectAccessLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Which projects came from a brief, and which part of the ask each takes.
 * The link is optional in both directions: a project may start without a brief
 * and be linked to one later, and a brief may never get a project (a dead end).
 * See docs/brief-entity.md.
 */
class BriefProjectController extends Controller
{
    /** Link from the brief page. */
    public function storeFromBrief(Request $request, Brief $brief): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer'],
            'scope_note' => ['nullable', 'string'],
        ]);

        $this->link($brief, $this->findProject($validated['project_id']), $validated['scope_note'] ?? null);

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

        $this->link($brief, $project, $validated['scope_note'] ?? null);

        return back();
    }

    public function update(Request $request, Brief $brief, Project $project): RedirectResponse
    {
        $this->authorizeLink($brief, $project);

        $validated = $request->validate(['scope_note' => ['nullable', 'string']]);

        $brief->projects()->updateExistingPivot($project->getKey(), ['scope_note' => $validated['scope_note'] ?? null]);

        return back();
    }

    public function destroy(Brief $brief, Project $project): RedirectResponse
    {
        $this->authorizeLink($brief, $project);

        $brief->projects()->detach($project->getKey());

        return back();
    }

    /**
     * Start a project from an approved brief. Linking an existing project does not
     * need approval; starting a new one from the ask does.
     */
    public function createProject(Request $request, Brief $brief): RedirectResponse
    {
        EntityAccess::authorize($request->user(), 'Brief', EntityAccess::UPDATE);
        EntityAccess::authorize($request->user(), 'Project', EntityAccess::CREATE);

        if (! $brief->isApproved()) {
            throw ValidationException::withMessages(['name' => __('ui.brief_must_be_approved')]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'scope_note' => ['nullable', 'string'],
        ]);

        $project = Project::query()->create([
            'workspace_id' => $brief->workspace_id,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
        ]);

        $brief->projects()->attach($project->getKey(), ['scope_note' => $validated['scope_note'] ?? null]);

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

    protected function link(Brief $brief, Project $project, ?string $scopeNote): void
    {
        $this->authorizeLink($brief, $project);

        if ((int) $project->workspace_id !== (int) $brief->workspace_id) {
            throw ValidationException::withMessages(['project_id' => __('ui.brief_same_workspace')]);
        }

        // Linking twice only updates the note.
        $brief->projects()->syncWithoutDetaching([$project->getKey() => ['scope_note' => $scopeNote]]);
    }

    protected function authorizeLink(Brief $brief, Project $project): void
    {
        EntityAccess::authorize(auth()->user(), 'Brief', EntityAccess::UPDATE);
        ProjectAccess::assertCan([(int) $project->getKey()], ProjectAccessLevel::Edit);
    }
}
