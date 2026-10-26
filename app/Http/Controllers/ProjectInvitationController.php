<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Services\ProjectInvitationService;
use App\Support\ProjectAccess;
use App\Support\ProjectAccessLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The inviter side of invitations: send, change the level, resend, revoke.
 * Same rule as the members tab: only people who may manage the project's members.
 */
class ProjectInvitationController extends Controller
{
    public function __construct(protected ProjectInvitationService $invitations)
    {
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeManage($project);

        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'access' => ['required', Rule::in(ProjectAccessLevel::values())],
        ]);

        $this->invitations->invite($project, $validated['email'], ProjectAccessLevel::from($validated['access']), $request->user());

        return $this->back($project, __('ui.invite_sent'));
    }

    public function update(Request $request, Project $project, ProjectInvitation $invitation): RedirectResponse
    {
        $this->authorizeManage($project);
        $this->assertBelongs($project, $invitation);

        $validated = $request->validate([
            'access' => ['required', Rule::in(ProjectAccessLevel::values())],
        ]);

        $this->invitations->updateLevel($invitation, ProjectAccessLevel::from($validated['access']));

        return $this->back($project, __('ui.members_saved'));
    }

    public function resend(Project $project, ProjectInvitation $invitation): RedirectResponse
    {
        $this->authorizeManage($project);
        $this->assertBelongs($project, $invitation);

        $this->invitations->resend($invitation);

        return $this->back($project, __('ui.invite_sent'));
    }

    public function revoke(Project $project, ProjectInvitation $invitation): RedirectResponse
    {
        $this->authorizeManage($project);
        $this->assertBelongs($project, $invitation);

        $this->invitations->revoke($invitation);

        return $this->back($project, __('ui.invite_revoked'));
    }

    protected function authorizeManage(Project $project): void
    {
        abort_unless(ProjectAccess::canManageMembers(auth()->user(), $project), 403);
    }

    protected function assertBelongs(Project $project, ProjectInvitation $invitation): void
    {
        abort_unless((int) $invitation->project_id === (int) $project->getKey(), 404);
    }

    protected function back(Project $project, string $status): RedirectResponse
    {
        return redirect()->route('projects.members.index', $project)->with('status', $status);
    }
}
