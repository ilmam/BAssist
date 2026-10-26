<?php

namespace Tests\Feature;

use App\Mail\ProjectInvitationMail;
use App\Models\ProjectInvitation;
use App\Models\ProjectMember;
use App\Models\User;
use App\Support\ProjectAccessLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\SeedsProjectAccess;
use Tests\TestCase;

class ProjectInvitationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProjectAccess;

    /** @var array<string, mixed> */
    protected array $a;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->a = $this->seedTenant('Alpha');
        $this->manager = $this->manager($this->a['tenant'], $this->a['workspace']);
    }

    protected function invite(string $email, string $access = 'comment'): void
    {
        $this->actingAs($this->manager)->post(
            route('projects.invitations.store', $this->a['project']),
            ['email' => $email, 'access' => $access],
        );
        auth()->logout();
    }

    protected function lastToken(): string
    {
        $mail = Mail::sent(ProjectInvitationMail::class)->last();

        return basename(parse_url($mail->url, PHP_URL_PATH));
    }

    /** @return array<string, string> */
    protected function registration(): array
    {
        return ['name' => 'Nora Newcomer', 'password' => 'Str0ng-Passw0rd!x', 'password_confirmation' => 'Str0ng-Passw0rd!x'];
    }

    public function test_manager_invites_and_mail_is_sent_with_a_link(): void
    {
        $this->actingAs($this->manager)
            ->post(route('projects.invitations.store', $this->a['project']), ['email' => 'new@example.com', 'access' => 'comment'])
            ->assertRedirect(route('projects.members.index', $this->a['project']));

        Mail::assertSent(ProjectInvitationMail::class, fn ($mail) => $mail->hasTo('new@example.com') && str_contains($mail->url, '/invitations/'));
        $invitation = ProjectInvitation::query()->firstOrFail();
        $this->assertSame(ProjectAccessLevel::Comment, $invitation->access);
        $this->assertSame($this->a['project']->id, $invitation->project_id);
        $this->assertSame($this->manager->id, $invitation->invited_by);
        $this->assertTrue($invitation->expires_at->isFuture());
    }

    public function test_token_is_stored_hashed(): void
    {
        $this->invite('new@example.com');

        $token = $this->lastToken();
        $invitation = ProjectInvitation::query()->firstOrFail();
        $this->assertSame(hash('sha256', $token), $invitation->token_hash);
        $this->assertNotSame($token, $invitation->token_hash);
    }

    public function test_email_is_normalised_to_lowercase(): void
    {
        $this->invite('  New@Example.COM ');

        $this->assertSame('new@example.com', ProjectInvitation::query()->firstOrFail()->email);
    }

    public function test_inviting_the_same_email_twice_keeps_one_pending_invitation_and_updates_the_level(): void
    {
        $this->invite('new@example.com', 'view');
        $first = $this->lastToken();
        $this->invite('NEW@example.com', 'edit');

        $this->assertSame(1, ProjectInvitation::query()->count());
        $this->assertSame(ProjectAccessLevel::Edit, ProjectInvitation::query()->firstOrFail()->access);
        Mail::assertSent(ProjectInvitationMail::class, 2);
        $this->get(route('invitations.show', $first))->assertStatus(410);
        $this->get(route('invitations.show', $this->lastToken()))->assertOk();
    }

    public function test_cannot_invite_a_user_of_the_projects_own_tenant_or_an_existing_member(): void
    {
        $colleague = $this->analystIn($this->a['tenant'], $this->a['workspace']);
        $guest = User::factory()->guest()->create(['email' => 'already@example.com']);
        $this->grant($guest, $this->a['project'], ProjectAccessLevel::View);

        $this->actingAs($this->manager)
            ->post(route('projects.invitations.store', $this->a['project']), ['email' => $colleague->email, 'access' => 'view'])
            ->assertSessionHasErrors('email');
        $this->post(route('projects.invitations.store', $this->a['project']), ['email' => 'already@example.com', 'access' => 'view'])
            ->assertSessionHasErrors('email');

        $this->assertSame(0, ProjectInvitation::query()->count());
        Mail::assertNothingSent();
    }

    public function test_only_managers_can_invite(): void
    {
        $analyst = $this->analystIn($this->a['tenant'], $this->a['workspace']);
        $guest = User::factory()->guest()->create();
        $this->grant($guest, $this->a['project'], ProjectAccessLevel::Edit);
        $body = ['email' => 'new@example.com', 'access' => 'view'];

        $this->actingAs($analyst)->post(route('projects.invitations.store', $this->a['project']), $body)->assertForbidden();
        $this->actingAs($guest)->post(route('projects.invitations.store', $this->a['project']), $body)->assertForbidden();

        Mail::assertNothingSent();
        $this->assertSame(0, ProjectInvitation::query()->count());
    }

    public function test_invalid_level_or_email_is_rejected(): void
    {
        $this->actingAs($this->manager)
            ->post(route('projects.invitations.store', $this->a['project']), ['email' => 'not-an-email', 'access' => 'owner'])
            ->assertSessionHasErrors(['email', 'access']);
    }

    public function test_new_person_accepts_and_gets_a_guest_account_and_grant(): void
    {
        config(['tenancy.auto_assign' => true, 'tenancy.mode' => 'shared']);
        $this->invite('nora@example.com', 'comment');
        $token = $this->lastToken();

        $this->get(route('invitations.show', $token))->assertOk()->assertSee('Alpha project');
        $this->post(route('invitations.accept', $token), $this->registration())
            ->assertRedirect(route('projects.dashboard', $this->a['project']));

        $user = User::query()->where('email', 'nora@example.com')->firstOrFail();
        $this->assertTrue($user->is_guest);
        $this->assertNull($user->tenant_id);
        $this->assertNull($user->role_id);
        $this->assertSame(ProjectAccessLevel::Comment, ProjectMember::query()->where('user_id', $user->id)->firstOrFail()->access);
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->tenant_id);
        $this->get(route('projects.dashboard', $this->a['project']))->assertOk();
        $this->assertNotNull(ProjectInvitation::query()->firstOrFail()->accepted_at);
    }

    public function test_pending_level_change_applies_on_acceptance(): void
    {
        $this->invite('nora@example.com', 'view');
        $token = $this->lastToken();
        $invitation = ProjectInvitation::query()->firstOrFail();

        $this->actingAs($this->manager)
            ->patch(route('projects.invitations.update', [$this->a['project'], $invitation]), ['access' => 'edit'])
            ->assertRedirect();
        auth()->logout();

        $this->post(route('invitations.accept', $token), $this->registration());

        $user = User::query()->where('email', 'nora@example.com')->firstOrFail();
        $this->assertSame(ProjectAccessLevel::Edit, ProjectMember::query()->where('user_id', $user->id)->firstOrFail()->access);
    }

    public function test_existing_user_must_be_logged_in_as_the_invited_email(): void
    {
        $existing = User::factory()->guest()->create(['email' => 'gina@example.com']);
        $other = User::factory()->guest()->create(['email' => 'other@example.com']);
        $this->invite('gina@example.com', 'view');
        $token = $this->lastToken();

        $this->get(route('invitations.show', $token))->assertRedirect(route('login'));

        $this->actingAs($other)->get(route('invitations.show', $token))->assertForbidden();
        $this->post(route('invitations.accept', $token))->assertForbidden();
        $this->assertSame(0, ProjectMember::query()->where('user_id', $other->id)->count());

        $this->actingAs($existing)->post(route('invitations.accept', $token))
            ->assertRedirect(route('projects.dashboard', $this->a['project']));
        $this->assertSame(ProjectAccessLevel::View, ProjectMember::query()->where('user_id', $existing->id)->firstOrFail()->access);
    }

    public function test_existing_guest_invited_to_a_second_tenant_gets_a_second_grant(): void
    {
        $b = $this->seedTenant('Bravo');
        $guest = User::factory()->guest()->create(['email' => 'gina@example.com']);
        $this->grant($guest, $this->a['project'], ProjectAccessLevel::View);

        $bravoManager = $this->manager($b['tenant'], $b['workspace']);
        $this->actingAs($bravoManager)->post(route('projects.invitations.store', $b['project']), ['email' => 'gina@example.com', 'access' => 'edit']);
        $token = $this->lastToken();

        $this->actingAs($guest)->post(route('invitations.accept', $token))->assertRedirect(route('projects.dashboard', $b['project']));

        $this->assertSame(2, ProjectMember::query()->where('user_id', $guest->id)->count());
        $this->assertSame(ProjectAccessLevel::Edit, ProjectMember::query()->where('user_id', $guest->id)->where('project_id', $b['project']->id)->firstOrFail()->access);
    }

    public function test_expired_revoked_and_used_tokens_are_gone(): void
    {
        $this->invite('exp@example.com');
        $expired = $this->lastToken();
        ProjectInvitation::query()->where('email', 'exp@example.com')->update(['expires_at' => now()->subMinute()]);

        $this->invite('rev@example.com');
        $revoked = $this->lastToken();
        $invitation = ProjectInvitation::query()->where('email', 'rev@example.com')->firstOrFail();
        $this->actingAs($this->manager)
            ->delete(route('projects.invitations.revoke', [$this->a['project'], $invitation]))
            ->assertRedirect();
        auth()->logout();

        $this->invite('used@example.com');
        $used = $this->lastToken();
        $this->post(route('invitations.accept', $used), $this->registration())->assertRedirect();
        auth()->logout();

        foreach ([$expired, $revoked, $used] as $token) {
            $this->get(route('invitations.show', $token))->assertStatus(410);
            $this->post(route('invitations.accept', $token), $this->registration())->assertStatus(410);
        }
        $this->get(route('invitations.show', 'not-a-real-token'))->assertStatus(410);
    }

    public function test_resend_invalidates_the_previous_link(): void
    {
        $this->invite('nora@example.com');
        $first = $this->lastToken();
        $invitation = ProjectInvitation::query()->firstOrFail();

        $this->actingAs($this->manager)
            ->post(route('projects.invitations.resend', [$this->a['project'], $invitation]))
            ->assertRedirect();
        auth()->logout();

        Mail::assertSent(ProjectInvitationMail::class, 2);
        $this->get(route('invitations.show', $first))->assertStatus(410);
        $this->get(route('invitations.show', $this->lastToken()))->assertOk();
    }

    public function test_invitation_actions_belong_to_the_project_in_the_url(): void
    {
        $other = $this->seedSiblingProject($this->a['workspace'], 'Sibling');
        $this->invite('nora@example.com');
        $invitation = ProjectInvitation::query()->firstOrFail();

        $this->actingAs($this->manager)
            ->delete(route('projects.invitations.revoke', [$other['project'], $invitation]))
            ->assertNotFound();
        $this->assertNull($invitation->fresh()->revoked_at);
    }
}
