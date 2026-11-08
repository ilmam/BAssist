<?php

namespace App\Services;

use App\Models\Brief;
use App\Models\Project;
use App\Support\BriefStatus;
use App\Support\EntityAccess;
use App\Support\ProjectAccess;
use App\Support\ProjectAccessLevel;
use Illuminate\Validation\ValidationException;

/**
 * Which projects came from a brief. Shared by the web controller and the MCP
 * tool so both apply the same rules: same workspace, role permission, project
 * edit access, and no new project from a declined brief. See docs/brief-entity.md.
 */
class BriefProjectService
{
    /** Link a project to a brief. Linking twice only updates the note. */
    public function link(Brief $brief, Project $project, ?string $scopeNote): void
    {
        $this->authorize($brief, $project);

        if ((int) $project->workspace_id !== (int) $brief->workspace_id) {
            throw ValidationException::withMessages(['project_id' => __('ui.brief_same_workspace')]);
        }

        $brief->projects()->syncWithoutDetaching([$project->getKey() => ['scope_note' => $scopeNote]]);
    }

    public function updateNote(Brief $brief, Project $project, ?string $scopeNote): void
    {
        $this->authorize($brief, $project);

        $brief->projects()->updateExistingPivot($project->getKey(), ['scope_note' => $scopeNote]);
    }

    public function unlink(Brief $brief, Project $project): void
    {
        $this->authorize($brief, $project);

        $brief->projects()->detach($project->getKey());
    }

    /** Start a new project in the brief's workspace and link it. */
    public function createProject(Brief $brief, string $name, ?string $code, ?string $scopeNote): Project
    {
        EntityAccess::authorize(auth()->user(), 'Brief', EntityAccess::UPDATE);
        EntityAccess::authorize(auth()->user(), 'Project', EntityAccess::CREATE);

        if ($brief->status === BriefStatus::DECLINED) {
            throw ValidationException::withMessages(['name' => __('ui.brief_must_be_approved')]);
        }

        $project = Project::query()->create([
            'workspace_id' => $brief->workspace_id,
            'name' => $name,
            'code' => $code,
        ]);

        $brief->projects()->attach($project->getKey(), ['scope_note' => $scopeNote]);

        return $project;
    }

    protected function authorize(Brief $brief, Project $project): void
    {
        EntityAccess::authorize(auth()->user(), 'Brief', EntityAccess::UPDATE);
        ProjectAccess::assertCan([(int) $project->getKey()], ProjectAccessLevel::Edit);
    }
}
