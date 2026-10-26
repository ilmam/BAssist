<?php

namespace Tests\Feature;

use App\Models\FunctionalRequirement;
use App\Models\ProjectInvitation;
use App\Models\ProjectMember;
use App\Models\User;
use App\Support\ProjectAccessLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsProjectAccess;
use Tests\TestCase;

class ProjectMembersTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProjectAccess;

    /** @var array<string, mixed> */
    protected array $a;

    protected User $manager;

    protected User $guest;

    protected ProjectMember $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->a = $this->seedTenant('Alpha');
        $this->manager = $this->manager($this->a['tenant'], $this->a['workspace']);
        $this->guest = User::factory()->guest()->create(['name' => 'Gina Guest', 'email' => 'gina@example.com']);
        $this->member = $this->grant($this->guest, $this->a['project'], ProjectAccessLevel::Edit);
    }

    protected function payload(): array
    {
        return [
            'title' => 'Changed title',
            'project_id' => $this->a['project']->id,
            'stakeholder_need_id' => $this->a['need']->id,
            'change_request_id' => null,
            'statement' => 'The system shall be changed.',
            'acceptance_criteria' => 'Given a change',
        ];
    }

    public function test_manager_sees_members_and_pending_invitations(): void
    {
        ProjectInvitation::query()->create([
            'project_id' => $this->a['project']->id,
            'email' => 'pending@example.com',
            'access' => ProjectAccessLevel::Comment,
            'token_hash' => hash('sha256', 'x'),
            'invited_by' => $this->manager->id,
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($this->manager)
            ->get(route('projects.members.index', $this->a['project']))
            ->assertOk()
            ->assertSee('Gina Guest')
            ->assertSee('gina@example.com')
            ->assertSee('pending@example.com')
            ->assertSee(route('projects.invitations.store', $this->a['project']))
            ->assertSee(route('projects.invitations.revoke', [$this->a['project'], ProjectInvitation::query()->firstOrFail()]));
    }

    public function test_dashboard_links_to_members_only_for_managers(): void
    {
        $members = route('projects.members.index', $this->a['project']);
        $analyst = $this->analystIn($this->a['tenant'], $this->a['workspace']);

        $this->actingAs($this->manager)->get(route('projects.dashboard', $this->a['project']))->assertOk()->assertSee($members);
        $this->actingAs($analyst)->get(route('projects.dashboard', $this->a['project']))->assertOk()->assertDontSee($members);
        $this->actingAs($this->guest)->get(route('projects.dashboard', $this->a['project']))->assertOk()->assertDontSee($members);
    }

    public function test_manager_changes_a_level(): void
    {
        $this->actingAs($this->manager)
            ->patch(route('projects.members.update', [$this->a['project'], $this->member]), ['access' => 'view'])
            ->assertRedirect(route('projects.members.index', $this->a['project']));

        $this->assertSame(ProjectAccessLevel::View, $this->member->fresh()->access);
    }

    public function test_manager_removes_a_member(): void
    {
        $this->actingAs($this->manager)
            ->delete(route('projects.members.destroy', [$this->a['project'], $this->member]))
            ->assertRedirect(route('projects.members.index', $this->a['project']));

        $this->assertNull(ProjectMember::query()->find($this->member->id));
    }

    public function test_analyst_without_project_update_gets_403(): void
    {
        $analyst = $this->analystIn($this->a['tenant'], $this->a['workspace']);

        $this->actingAs($analyst)->get(route('projects.members.index', $this->a['project']))->assertForbidden();
        $this->patch(route('projects.members.update', [$this->a['project'], $this->member]), ['access' => 'view'])->assertForbidden();
        $this->delete(route('projects.members.destroy', [$this->a['project'], $this->member]))->assertForbidden();
    }

    public function test_guest_gets_403(): void
    {
        $this->actingAs($this->guest)->get(route('projects.members.index', $this->a['project']))->assertForbidden();
        $this->patch(route('projects.members.update', [$this->a['project'], $this->member]), ['access' => 'view'])->assertForbidden();
        $this->assertSame(ProjectAccessLevel::Edit, $this->member->fresh()->access);
    }

    public function test_manager_of_another_tenant_gets_404(): void
    {
        $b = $this->seedTenant('Bravo');
        $bravoManager = $this->manager($b['tenant'], $b['workspace']);

        $this->actingAs($bravoManager)->get(route('projects.members.index', $this->a['project']))->assertNotFound();
    }

    public function test_member_of_another_project_is_404(): void
    {
        $other = $this->seedSiblingProject($this->a['workspace'], 'Sibling');
        $foreignMember = $this->grant($this->guest, $other['project'], ProjectAccessLevel::View);

        $this->actingAs($this->manager)
            ->patch(route('projects.members.update', [$this->a['project'], $foreignMember]), ['access' => 'edit'])
            ->assertNotFound();
        $this->assertSame(ProjectAccessLevel::View, $foreignMember->fresh()->access);
    }

    public function test_invalid_level_is_rejected(): void
    {
        $this->actingAs($this->manager)
            ->patch(route('projects.members.update', [$this->a['project'], $this->member]), ['access' => 'owner'])
            ->assertSessionHasErrors('access');

        $this->assertSame(ProjectAccessLevel::Edit, $this->member->fresh()->access);
    }

    public function test_downgrade_applies_on_the_next_request_even_with_cached_nav(): void
    {
        $this->actingAs($this->guest)->get(route('home'))->assertOk()->assertSee('Alpha project');
        $this->put(route('functional_requirements.update', $this->a['fr']->id), $this->payload())->assertRedirect();

        $this->actingAs($this->manager)
            ->patch(route('projects.members.update', [$this->a['project'], $this->member]), ['access' => 'view'])
            ->assertRedirect();

        $this->actingAs($this->guest)
            ->put(route('functional_requirements.update', $this->a['fr']->id), $this->payload())
            ->assertForbidden();
        $this->get(route('home'))->assertOk()->assertSee('Alpha project');
        $this->assertSame('Changed title', FunctionalRequirement::withoutGlobalScopes()->find($this->a['fr']->id)->title);
    }

    public function test_removal_hides_the_project_even_with_cached_nav(): void
    {
        $this->actingAs($this->guest)->get(route('home'))->assertOk()->assertSee('Alpha project');

        $this->actingAs($this->manager)->delete(route('projects.members.destroy', [$this->a['project'], $this->member]));

        $this->actingAs($this->guest)->get(route('projects.dashboard', $this->a['project']))->assertNotFound();
        $this->get(route('home'))->assertOk()->assertDontSee('Alpha project');
    }
}
