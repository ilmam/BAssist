<?php

namespace Tests\Feature;

use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Models\StakeholderNeed;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\ProjectRepository;
use App\Support\EntityAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A user of tenant A must never read, change, delete or reference tenant B's
 * records — by id in the URL, through the JSON API, or via ids in a payload.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** @var array{tenant: Tenant, workspace: Workspace, project: Project, need: StakeholderNeed, fr: FunctionalRequirement} */
    protected array $a;

    /** @var array{tenant: Tenant, workspace: Workspace, project: Project, need: StakeholderNeed, fr: FunctionalRequirement} */
    protected array $b;

    protected User $userA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->a = $this->seedTenant('Alpha');
        $this->b = $this->seedTenant('Bravo');
        $this->userA = $this->analystIn($this->a['tenant'], $this->a['workspace']);
    }

    public function test_web_show_edit_update_and_destroy_of_another_tenants_record_are_not_found(): void
    {
        $foreign = $this->b['fr'];
        $this->actingAs($this->userA);

        $this->get(route('functional_requirements.show', $foreign->id))->assertNotFound();
        $this->get(route('functional_requirements.edit', $foreign->id))->assertNotFound();

        $this->put(route('functional_requirements.update', $foreign->id), [
            'title' => 'Hijacked',
            'project_id' => $this->a['project']->id,
            'stakeholder_need_id' => $this->a['need']->id,
            'statement' => 'Hijacked',
        ])->assertNotFound();

        $this->delete(route('functional_requirements.destroy', $foreign->id))->assertNotFound();

        $fresh = FunctionalRequirement::withoutGlobalScopes()->find($foreign->id);
        $this->assertSame('Bravo requirement', $fresh->title);
        $this->assertNull($fresh->deleted_at);
    }

    public function test_own_records_remain_accessible(): void
    {
        $this->actingAs($this->userA);

        $this->get(route('functional_requirements.show', $this->a['fr']->id))->assertOk();
        $this->getJson(route('api.functional_requirement.show', $this->a['fr']->id))
            ->assertOk()
            ->assertJsonPath('title', 'Alpha requirement');
    }

    public function test_api_show_update_and_destroy_of_another_tenants_record_are_not_found(): void
    {
        $foreign = $this->b['fr'];
        $this->actingAs($this->userA);

        $this->getJson(route('api.functional_requirement.show', $foreign->id))->assertNotFound();
        $this->putJson(route('api.functional_requirement.update', $foreign->id), [
            'title' => 'Hijacked',
            'project_id' => $this->a['project']->id,
            'stakeholder_need_id' => $this->a['need']->id,
            'statement' => 'Hijacked',
        ])->assertNotFound();
        $this->deleteJson(route('api.functional_requirement.destroy', $foreign->id))->assertNotFound();

        $this->assertSame('Bravo requirement', FunctionalRequirement::withoutGlobalScopes()->find($foreign->id)->title);
    }

    public function test_list_api_only_returns_own_tenant_rows(): void
    {
        $this->actingAs($this->userA);

        $this->getJson(route('api.functional_requirement.index', ['project_id' => $this->b['project']->id]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 0)
            ->assertJsonMissing(['title' => 'Bravo requirement']);
    }

    public function test_create_rejects_a_project_from_another_tenant(): void
    {
        $this->actingAs($this->userA);

        $this->post(route('functional_requirements.store'), [
            'title' => 'Planted in Bravo',
            'project_id' => $this->b['project']->id,
            'stakeholder_need_id' => $this->b['need']->id,
            'statement' => 'Planted',
        ])->assertSessionHasErrors(['project_id', 'stakeholder_need_id']);

        $this->assertFalse(FunctionalRequirement::withoutGlobalScopes()->where('title', 'Planted in Bravo')->exists());
    }

    public function test_update_rejects_a_parent_from_another_tenant(): void
    {
        $own = $this->a['fr'];
        $this->actingAs($this->userA);

        $this->putJson(route('api.functional_requirement.update', $own->id), [
            'title' => $own->title,
            'project_id' => $this->a['project']->id,
            'stakeholder_need_id' => $this->b['need']->id,
            'statement' => $own->statement,
        ])->assertUnprocessable()->assertJsonValidationErrors('stakeholder_need_id');

        $this->assertSame($this->a['need']->id, (int) $own->fresh()->stakeholder_need_id);
    }

    public function test_project_pages_of_another_tenant_are_not_found(): void
    {
        $foreign = $this->b['project'];
        $this->actingAs($this->userA);

        $this->get(route('projects.dashboard', $foreign))->assertNotFound();
        $this->get(route('projects.export', $foreign))->assertNotFound();
        $this->get(route('projects.babok.index', $foreign))->assertNotFound();
        $this->get(route('strategic_baselines.for-project', $foreign))->assertNotFound();
        $this->get(route('architectures.for-project', $foreign))->assertNotFound();
    }

    public function test_user_without_a_tenant_joins_the_default_tenant(): void
    {
        config(['tenancy.mode' => 'shared']);
        $newcomer = $this->analystIn(null, null);
        $this->actingAs($newcomer);

        $this->getJson(route('api.functional_requirement.index'))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 0);

        $default = Tenant::query()->where('slug', config('tenancy.shared.tenant_slug'))->first();
        $this->assertNotNull($default);
        $this->assertSame($default->id, (int) $newcomer->fresh()->tenant_id);
        $this->get(route('functional_requirements.show', $this->a['fr']->id))->assertNotFound();
    }

    public function test_user_without_a_tenant_sees_nothing_when_auto_assign_is_off(): void
    {
        config(['tenancy.auto_assign' => false]);
        $orphan = $this->analystIn(null, null);
        $this->actingAs($orphan);

        $this->get(route('functional_requirements.show', $this->a['fr']->id))->assertNotFound();
        $this->getJson(route('api.functional_requirement.index'))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 0);
        $this->assertNull($orphan->fresh()->tenant_id);
    }

    public function test_select_options_never_offer_another_tenants_records(): void
    {
        $this->actingAs($this->userA);

        $names = app(ProjectRepository::class)->getSelectOptions()->all();

        $this->assertContains('Alpha project', $names);
        $this->assertNotContains('Bravo project', $names);
    }

    /**
     * @return array{tenant: Tenant, workspace: Workspace, project: Project, need: StakeholderNeed, fr: FunctionalRequirement}
     */
    protected function seedTenant(string $name): array
    {
        $tenant = Tenant::query()->create(['name' => $name, 'slug' => strtolower($name)]);
        $workspace = Workspace::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $name.' workspace',
            'slug' => strtolower($name).'-ws',
        ]);
        $project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => $name.' project',
            'code' => strtoupper(substr($name, 0, 3)),
        ]);
        $need = StakeholderNeed::query()->create([
            'project_id' => $project->id,
            'title' => $name.' need',
        ]);
        $fr = FunctionalRequirement::query()->create([
            'project_id' => $project->id,
            'stakeholder_need_id' => $need->id,
            'title' => $name.' requirement',
            'statement' => 'The system shall serve '.$name.'.',
        ]);

        return compact('tenant', 'workspace', 'project', 'need', 'fr');
    }

    protected function analystIn(?Tenant $tenant, ?Workspace $workspace): User
    {
        $role = Role::query()->firstOrCreate(['slug' => 'analyst'], ['name' => 'Analyst']);

        foreach (['FunctionalRequirement', 'Project', 'StakeholderNeed', 'StrategicBaseline', 'Architecture'] as $entity) {
            RoleEntityPermission::query()->firstOrCreate(
                ['role_id' => $role->id, 'entity' => $entity],
                ['can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true],
            );
        }

        $this->assertNotSame(EntityAccess::SUPER_ADMIN_SLUG, $role->slug);

        return User::factory()->create([
            'role_id' => $role->id,
            'tenant_id' => $tenant?->id,
            'workspace_id' => $workspace?->id,
        ]);
    }
}
