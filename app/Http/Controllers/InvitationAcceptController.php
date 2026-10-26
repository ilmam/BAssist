<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Services\ProjectInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * The public page an invitation email links to. The token is the secret; an
 * unknown, expired, revoked or used token is the same "gone" page. Someone who
 * already has an account must sign in as the invited address first.
 */
class InvitationAcceptController extends Controller
{
    use PasswordValidationRules;

    public function __construct(protected ProjectInvitationService $invitations)
    {
    }

    public function show(string $token): View|RedirectResponse|Response
    {
        $invitation = $this->invitations->findUsable($token);

        if ($invitation === null) {
            return $this->gone();
        }

        $user = auth()->user();

        if ($user !== null) {
            $this->assertInvitedUser($user, $invitation);

            return view('pages.invitations.show', ['invitation' => $invitation, 'token' => $token, 'register' => false]);
        }

        if ($this->accountExists($invitation)) {
            return redirect()->guest(route('login'));
        }

        return view('pages.invitations.show', ['invitation' => $invitation, 'token' => $token, 'register' => true]);
    }

    public function accept(Request $request, string $token): RedirectResponse|Response
    {
        $invitation = $this->invitations->findUsable($token);

        if ($invitation === null) {
            return $this->gone();
        }

        $user = $request->user();

        if ($user !== null) {
            $this->assertInvitedUser($user, $invitation);
            $this->invitations->accept($invitation, $user, null);

            return $this->toProject($invitation);
        }

        if ($this->accountExists($invitation)) {
            return redirect()->guest(route('login'));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => $this->passwordRules(),
        ]);

        $user = $this->invitations->accept($invitation, null, $validated);

        Auth::login($user);
        $request->session()->regenerate();

        return $this->toProject($invitation);
    }

    protected function assertInvitedUser(User $user, ProjectInvitation $invitation): void
    {
        abort_unless(ProjectInvitationService::normalise($user->email) === $invitation->email, 403);
    }

    protected function accountExists(ProjectInvitation $invitation): bool
    {
        return User::query()->whereRaw('lower(email) = ?', [$invitation->email])->exists();
    }

    protected function toProject(ProjectInvitation $invitation): RedirectResponse
    {
        return redirect()->route('projects.dashboard', $invitation->project_id);
    }

    protected function gone(): Response
    {
        return response()->view('pages.invitations.gone', [], 410);
    }
}
