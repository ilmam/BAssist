<?php

namespace Tests\Feature;

use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Models\Workspace;
use App\Support\ProjectAccessLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsProjectAccess;
use Tests\TestCase;

class GuestVisibilityTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProjectAccess;

    /** @var array<string, mixed> */
    protected array $a;

    /** @var array<string, mixed> */
    protected array $b;

    /** @var array<string, mixed> */
    protected array $a2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->a = $this->seedTenant('Alpha');
        $this->b = $this->seedTenant('Bravo');
        $this->a2 = $this->seedSiblingProject($this->a['workspace'], 'Alpha2');
    }

    public function test_guest_sees_only_the_granted_project(): void
    {
        $guest = User::factory()->guest()->create();
        $this->grant($guest, $this->a['project'], ProjectAccessLevel::View);
        $this->actingAs($guest);

        $this->assertSame(['Alpha project'], Project::query()->pluck('name')->all());
        $this->assertSame(['Alpha requirement'], FunctionalRequirement::query()->pluck('title')->all());
    }

    public function test_guest_sees_only_the_granted_workspace(): void
    {
        $guest = User::factory()->guest()->create();
        $this->grant($guest, $this->a['project'], ProjectAccessLevel::View);
        $this->actingAs($guest);

        $this->assertSame(['Alpha workspace'], Workspace::query()->pluck('name')->all());
    }

    public function test_guest_without_grants_sees_nothing(): void
    {
        $guest = User::factory()->guest()->create();
        $this->actingAs($guest);

        $this->assertSame(0, Project::query()->count());
        $this->assertSame(0, Workspace::query()->count());
        $this->assertSame(0, FunctionalRequirement::query()->count());
    }

    public function test_tenant_user_with_a_foreign_grant_sees_both(): void
    {
        $bravoUser = $this->analystIn($this->b['tenant'], $this->b['workspace']);
        $this->grant($bravoUser, $this->a['project'], ProjectAccessLevel::View);
        $this->actingAs($bravoUser);

        $this->assertEqualsCanonicalizing(['Alpha project', 'Bravo project'], Project::query()->pluck('name')->all());
        $this->assertEqualsCanonicalizing(['Alpha requirement', 'Bravo requirement'], FunctionalRequirement::query()->pluck('title')->all());
    }

    public function test_project_pages_resolve_only_for_the_granted_project(): void
    {
        $bravoUser = $this->analystIn($this->b['tenant'], $this->b['workspace']);
        $this->grant($bravoUser, $this->a['project'], ProjectAccessLevel::View);
        $this->actingAs($bravoUser);

        $this->get(route('projects.dashboard', $this->a['project']))->assertOk();
        $this->get(route('projects.export', $this->a['project']))->assertOk();
        $this->get(route('projects.dashboard', $this->a2['project']))->assertNotFound();
        $this->get(route('projects.export', $this->a2['project']))->assertNotFound();
    }

    public function test_revoking_the_grant_hides_the_project_on_the_next_request(): void
    {
        $bravoUser = $this->analystIn($this->b['tenant'], $this->b['workspace']);
        $member = $this->grant($bravoUser, $this->a['project'], ProjectAccessLevel::View);
        $this->actingAs($bravoUser);

        $this->get(route('projects.dashboard', $this->a['project']))->assertOk();

        $member->delete();

        $this->get(route('projects.dashboard', $this->a['project']))->assertNotFound();
        $this->assertSame(['Bravo project'], Project::query()->pluck('name')->all());
    }

    public function test_soft_deleted_granted_project_is_hidden(): void
    {
        $guest = User::factory()->guest()->create();
        $this->grant($guest, $this->a['project'], ProjectAccessLevel::View);
        $this->a['project']->delete();
        $this->actingAs($guest);

        $this->assertSame(0, Project::query()->count());
        $this->assertSame(0, FunctionalRequirement::query()->count());
        $this->assertSame(1, ProjectMember::query()->count());
    }
}
