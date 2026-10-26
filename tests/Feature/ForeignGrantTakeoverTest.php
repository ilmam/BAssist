<?php

namespace Tests\Feature;

use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Models\User;
use App\Models\Workspace;
use App\Support\ProjectAccessLevel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsProjectAccess;
use Tests\TestCase;

/**
 * A grant on a project of another tenant is a narrow window onto that
 * project's content. It must never let the grantee take over, move, rename or
 * delete the other tenant's workspace or the granted project itself, whatever
 * their own role allows, and never move records across tenants.
 */
class ForeignGrantTakeoverTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProjectAccess;

    /** @var array<string, mixed> */
    protected array $a;

    /** @var array<string, mixed> */
    protected array $b;

    protected User $bravoAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->a = $this->seedTenant('Alpha');
        $this->b = $this->seedTenant('Bravo');

        $role = Role::query()->create(['name' => 'Org admin', 'slug' => 'org-admin']);
        foreach (['Workspace', 'Project', 'FunctionalRequirement', 'StakeholderNeed'] as $entity) {
            RoleEntityPermission::query()->create([
                'role_id' => $role->id, 'entity' => $entity,
                'can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true,
            ]);
        }
        $this->bravoAdmin = User::factory()->create([
            'role_id' => $role->id,
            'tenant_id' => $this->b['tenant']->id,
            'workspace_id' => $this->b['workspace']->id,
        ]);
    }

    public function test_foreign_grantee_cannot_rename_move_or_delete_the_other_tenants_workspace(): void
    {
        $this->grant($this->bravoAdmin, $this->a['project'], ProjectAccessLevel::View);
        $this->actingAs($this->bravoAdmin);
        $workspace = Workspace::query()->findOrFail($this->a['workspace']->id);

        try {
            $workspace->update(['name' => 'Taken over', 'tenant_id' => $this->b['tenant']->id]);
            $this->fail('A foreign grantee changed another tenant workspace.');
        } catch (AuthorizationException) {
            // expected
        }
        $this->delete(route('workspaces.destroy', $workspace->id))->assertForbidden();

        $fresh = Workspace::withoutGlobalScopes()->find($workspace->id);
        $this->assertSame($this->a['tenant']->id, (int) $fresh->tenant_id);
        $this->assertSame('Alpha workspace', $fresh->name);
        $this->assertNull($fresh->deleted_at);
    }

    public function test_foreign_grantee_cannot_delete_move_or_plant_projects_in_the_other_tenant(): void
    {
        $this->grant($this->bravoAdmin, $this->a['project'], ProjectAccessLevel::Edit);
        $this->actingAs($this->bravoAdmin);
        $project = Project::query()->findOrFail($this->a['project']->id);

        $this->delete(route('projects.destroy', $project->id))->assertForbidden();

        try {
            $project->update(['workspace_id' => $this->b['workspace']->id]);
            $this->fail('A foreign grantee moved the granted project into their own tenant.');
        } catch (AuthorizationException) {
            // expected
        }

        try {
            Project::query()->create(['workspace_id' => $this->a['workspace']->id, 'name' => 'Planted', 'code' => 'PLA']);
            $this->fail('A foreign grantee created a project in another tenant.');
        } catch (AuthorizationException) {
            // expected
        }

        $fresh = Project::withoutGlobalScopes()->find($project->id);
        $this->assertSame($this->a['workspace']->id, (int) $fresh->workspace_id);
        $this->assertNull($fresh->deleted_at);
        $this->assertFalse(Project::withoutGlobalScopes()->where('name', 'Planted')->exists());
    }

    public function test_edit_grantee_cannot_move_a_foreign_record_into_their_own_tenant(): void
    {
        $this->grant($this->bravoAdmin, $this->a['project'], ProjectAccessLevel::Edit);
        $this->actingAs($this->bravoAdmin);

        $this->put(route('functional_requirements.update', $this->a['fr']->id), [
            'title' => 'Stolen',
            'project_id' => $this->b['project']->id,
            'stakeholder_need_id' => $this->b['need']->id,
            'change_request_id' => null,
            'statement' => 'Moved to Bravo.',
            'acceptance_criteria' => 'Given a move',
        ])->assertForbidden();

        $fresh = FunctionalRequirement::withoutGlobalScopes()->find($this->a['fr']->id);
        $this->assertSame($this->a['project']->id, (int) $fresh->project_id);
        $this->assertSame('Alpha requirement', $fresh->title);
    }

    public function test_tenant_admins_still_manage_their_own_workspaces_and_projects(): void
    {
        $this->actingAs($this->bravoAdmin);

        $workspace = Workspace::query()->findOrFail($this->b['workspace']->id);
        $workspace->update(['name' => 'Renamed']);
        $project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'New in Bravo', 'code' => 'NEW']);
        $project->update(['name' => 'Renamed project']);

        $this->assertSame('Renamed', $workspace->fresh()->name);
        $this->assertSame('Renamed project', $project->fresh()->name);
    }
}
