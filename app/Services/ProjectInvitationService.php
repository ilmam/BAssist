<?php

namespace App\Services;

use App\Mail\ProjectInvitationMail;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\ProjectMember;
use App\Models\User;
use App\Support\ProjectAccess;
use App\Support\ProjectAccessLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Inviting people to a project by email. The link carries a random token; only
 * its hash is stored, so a leaked database does not leak working links.
 */
class ProjectInvitationService
{
    public static function normalise(string $email): string
    {
        return Str::lower(trim($email));
    }

    /**
     * Invite an email address. A second invitation for the same address and
     * project updates the pending one (level, new link, new expiry) instead of
     * adding another.
     *
     * @throws ValidationException when the person already has access
     */
    public function invite(Project $project, string $email, ProjectAccessLevel $level, User $by): ProjectInvitation
    {
        $email = self::normalise($email);
        $this->assertInvitable($project, $email);

        $invitation = ProjectInvitation::query()
            ->pending()
            ->where('project_id', $project->getKey())
            ->where('email', $email)
            ->first() ?? new ProjectInvitation(['project_id' => $project->getKey(), 'email' => $email]);

        $invitation->fill(['access' => $level, 'invited_by' => $by->getKey()]);
        $this->send($invitation);

        return $invitation;
    }

    /** New link, new expiry; the previous link stops working. */
    public function resend(ProjectInvitation $invitation): void
    {
        abort_unless($invitation->isUsable(), 410);

        $this->send($invitation);
    }

    public function revoke(ProjectInvitation $invitation): void
    {
        if ($invitation->accepted_at === null && $invitation->revoked_at === null) {
            $invitation->forceFill(['revoked_at' => now()])->save();
        }
    }

    public function updateLevel(ProjectInvitation $invitation, ProjectAccessLevel $level): void
    {
        abort_unless($invitation->isUsable(), 410);

        $invitation->update(['access' => $level]);
    }

    public function findUsable(string $token): ?ProjectInvitation
    {
        $invitation = ProjectInvitation::query()->where('token_hash', hash('sha256', $token))->first();

        return $invitation !== null && $invitation->isUsable() ? $invitation : null;
    }

    /**
     * Turn an invitation into a grant. Without an existing user a guest account
     * is created from the registration data (no tenant, no role).
     *
     * @param  array{name: string, password: string}|null  $registration
     */
    public function accept(ProjectInvitation $invitation, ?User $existing, ?array $registration): User
    {
        return DB::transaction(function () use ($invitation, $existing, $registration): User {
            $user = $existing;

            if ($user === null) {
                $user = new User([
                    'name' => $registration['name'],
                    'email' => $invitation->email,
                    'password' => $registration['password'],
                    'is_guest' => true,
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            ProjectMember::query()->updateOrCreate(
                ['project_id' => $invitation->project_id, 'user_id' => $user->getKey()],
                ['access' => $invitation->access, 'invited_by' => $invitation->invited_by],
            );

            $invitation->forceFill(['accepted_at' => now()])->save();

            return $user;
        });
    }

    protected function send(ProjectInvitation $invitation): void
    {
        $token = Str::random(48);

        $invitation->forceFill([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays((int) config('tenancy.invitations.ttl_days', 7)),
        ])->save();

        Mail::to($invitation->email)->send(new ProjectInvitationMail($invitation, route('invitations.show', $token)));
    }

    /**
     * @throws ValidationException
     */
    protected function assertInvitable(Project $project, string $email): void
    {
        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if ($user === null) {
            return;
        }

        if (ProjectAccess::levelFor($user, (int) $project->getKey()) !== null) {
            throw ValidationException::withMessages(['email' => __('ui.invite_already_member')]);
        }

        $projectTenant = ProjectAccess::tenantIdOfProject((int) $project->getKey());

        if (! $user->is_guest && $user->tenant_id !== null && (int) $user->tenant_id === $projectTenant) {
            throw ValidationException::withMessages(['email' => __('ui.invite_same_tenant')]);
        }
    }
}
