<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\ProjectMember;
use App\Support\ProjectAccess;
use App\Support\ProjectAccessLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Who was invited to a project and at which level. The inviter side: change a
 * level, remove someone, see pending invitations. Changes apply on the person's
 * next request.
 */
class ProjectMemberController extends Controller
{
    public function index(Project $project): View
    {
        $this->authorizeManage($project);

        return view('pages.projects.members', [
            'project' => $project,
            'members' => ProjectMember::query()
                ->where('project_id', $project->getKey())
                ->with('user')
                ->get()
                ->sortBy(fn (ProjectMember $member) => mb_strtolower((string) $member->user?->name)),
            'invitations' => ProjectInvitation::query()
                ->pending()
                ->where('project_id', $project->getKey())
                ->orderBy('email')
                ->get(),
            'levels' => ProjectAccessLevel::cases(),
        ]);
    }

    public function update(Request $request, Project $project, ProjectMember $member): RedirectResponse
    {
        $this->authorizeManage($project);
        $this->assertBelongs($project, $member);

        $validated = $request->validate([
            'access' => ['required', Rule::in(ProjectAccessLevel::values())],
        ]);

        $member->update(['access' => $validated['access']]);

        return $this->back($project);
    }

    public function destroy(Project $project, ProjectMember $member): RedirectResponse
    {
        $this->authorizeManage($project);
        $this->assertBelongs($project, $member);

        $member->delete();

        return $this->back($project);
    }

    protected function authorizeManage(Project $project): void
    {
        abort_unless(ProjectAccess::canManageMembers(auth()->user(), $project), 403);
    }

    protected function assertBelongs(Project $project, ProjectMember $member): void
    {
        abort_unless((int) $member->project_id === (int) $project->getKey(), 404);
    }

    protected function back(Project $project): RedirectResponse
    {
        return redirect()->route('projects.members.index', $project)->with('status', __('ui.members_saved'));
    }
}
