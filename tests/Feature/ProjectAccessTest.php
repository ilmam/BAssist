<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\EntityAccess;
use App\Support\ProjectAccess;
use App\Support\ProjectAccessLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsProjectAccess;
use Tests\TestCase;

class ProjectAccessTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProjectAccess;

    public function test_level_for_returns_the_grant_level_or_null(): void
    {
        $a = $this->seedTenant('Alpha');
        $b = $this->seedTenant('Bravo');
        $guest = User::factory()->guest()->create();
        $this->grant($guest, $a['project'], ProjectAccessLevel::Comment);

        $this->assertSame(ProjectAccessLevel::Comment, ProjectAccess::levelFor($guest, $a['project']->id));
        $this->assertNull(ProjectAccess::levelFor($guest, $b['project']->id));
        $this->assertTrue(ProjectAccess::hasAnyGrant($guest));
        $this->assertSame(ProjectAccessLevel::Comment, ProjectAccess::maxLevel($guest));
    }

    public function test_foreign_project_is_governed_by_the_grant(): void
    {
        $a = $this->seedTenant('Alpha');
        $guest = User::factory()->guest()->create();
        $this->grant($guest, $a['project'], ProjectAccessLevel::Comment);

        $this->assertTrue(ProjectAccess::isForeign($guest, $a['project']->id));
        $this->assertTrue(ProjectAccess::can($guest, $a['project']->id, ProjectAccessLevel::Comment));
        $this->assertFalse(ProjectAccess::can($guest, $a['project']->id, ProjectAccessLevel::Edit));
    }

    public function test_own_tenant_project_is_never_foreign(): void
    {
        $a = $this->seedTenant('Alpha');
        $analyst = $this->analystIn($a['tenant'], $a['workspace']);

        $this->assertFalse(ProjectAccess::isForeign($analyst, $a['project']->id));
        $this->assertTrue(ProjectAccess::can($analyst, $a['project']->id, ProjectAccessLevel::Edit));
    }

    public function test_existing_user_of_another_tenant_is_foreign_for_the_invited_project(): void
    {
        $a = $this->seedTenant('Alpha');
        $b = $this->seedTenant('Bravo');
        $bravoUser = $this->analystIn($b['tenant'], $b['workspace']);

        $this->assertTrue(ProjectAccess::isForeign($bravoUser, $a['project']->id));
        $this->assertFalse(ProjectAccess::can($bravoUser, $a['project']->id, ProjectAccessLevel::View));

        $this->grant($bravoUser, $a['project'], ProjectAccessLevel::View);

        $this->assertTrue(ProjectAccess::can($bravoUser, $a['project']->id, ProjectAccessLevel::View));
        $this->assertFalse(ProjectAccess::can($bravoUser, $a['project']->id, ProjectAccessLevel::Comment));
    }

    public function test_can_manage_members_rules(): void
    {
        $a = $this->seedTenant('Alpha');
        $b = $this->seedTenant('Bravo');
        $project = $a['project'];

        $this->assertTrue(ProjectAccess::canManageMembers($this->manager($a['tenant'], $a['workspace']), $project));
        $this->assertFalse(ProjectAccess::canManageMembers($this->analystIn($a['tenant'], $a['workspace']), $project));
        $this->assertFalse(ProjectAccess::canManageMembers($this->manager($b['tenant'], $b['workspace']), $project));

        $guest = User::factory()->guest()->create();
        $this->grant($guest, $project, ProjectAccessLevel::Edit);
        $this->assertFalse(ProjectAccess::canManageMembers($guest, $project));

        $superRole = Role::query()->firstOrCreate(['slug' => EntityAccess::SUPER_ADMIN_SLUG], ['name' => 'Super admin']);
        $super = User::factory()->create(['role_id' => $superRole->id, 'tenant_id' => $a['tenant']->id]);
        $this->assertTrue(ProjectAccess::canManageMembers($super, $project));
    }

    public function test_guest_entity_abilities_follow_the_highest_grant(): void
    {
        $a = $this->seedTenant('Alpha');
        $guest = User::factory()->guest()->create();

        $this->assertFalse(ProjectAccess::guestAllows($guest, 'FunctionalRequirement', EntityAccess::VIEW));

        $this->grant($guest, $a['project'], ProjectAccessLevel::Comment);
        $this->assertTrue(ProjectAccess::guestAllows($guest, 'FunctionalRequirement', EntityAccess::VIEW));
        $this->assertFalse(ProjectAccess::guestAllows($guest, 'FunctionalRequirement', EntityAccess::UPDATE));

        $this->grant($guest, $a['project'], ProjectAccessLevel::Edit);
        $this->assertTrue(ProjectAccess::guestAllows($guest, 'FunctionalRequirement', EntityAccess::UPDATE));
        $this->assertTrue(ProjectAccess::guestAllows($guest, 'FunctionalRequirement', EntityAccess::DELETE));
        $this->assertFalse(ProjectAccess::guestAllows($guest, 'FunctionalRequirement', EntityAccess::APPROVE));
        $this->assertFalse(ProjectAccess::guestAllows($guest, 'Workspace', EntityAccess::VIEW));
        $this->assertTrue(ProjectAccess::guestAllows($guest, 'Project', EntityAccess::VIEW));
        $this->assertFalse(ProjectAccess::guestAllows($guest, 'Project', EntityAccess::UPDATE));
    }
}
