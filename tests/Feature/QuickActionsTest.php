<?php

namespace Tests\Feature;

use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Models\StakeholderNeed;
use App\Models\User;
use App\Services\TenancyProvisioner;
use App\Support\EntityAccess;
use App\Support\EntityStatus;
use Database\Seeders\StatusPrioritySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickActionsTest extends TestCase
{
    use RefreshDatabase;

    protected Project $project;

    protected FunctionalRequirement $fr;

    protected FunctionalRequirement $fr2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(StatusPrioritySeeder::class);
        EntityStatus::forgetCache();

        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);
        $this->project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Quick', 'code' => 'QCK']);
        $story = StakeholderNeed::query()->create(['project_id' => $this->project->id, 'title' => 'Dealer submits inquiry']);
        $this->fr = FunctionalRequirement::query()->create(['project_id' => $this->project->id, 'stakeholder_need_id' => $story->id, 'title' => 'Create ticket', 'statement' => 'x']);
        $this->fr2 = FunctionalRequirement::query()->create(['project_id' => $this->project->id, 'stakeholder_need_id' => $story->id, 'title' => 'Attach files', 'statement' => 'y']);
    }

    protected function user(string $slug, array $perms = []): User
    {
        $role = Role::query()->create(['name' => $slug, 'slug' => $slug]);
        foreach ($perms as $entity => $flags) {
            RoleEntityPermission::query()->create(['role_id' => $role->id, 'entity' => $entity] + $flags);
        }
        $tenant = app(TenancyProvisioner::class)->ensureSharedTenant();

        return User::factory()->create(['role_id' => $role->id, 'tenant_id' => $tenant->id, 'workspace_id' => $this->project->workspace_id]);
    }

    public function test_search_finds_by_code_and_title(): void
    {
        $this->actingAs($this->user(EntityAccess::SUPER_ADMIN_SLUG));

        $this->getJson(route('quick.search', ['q' => 'FR-2']))
            ->assertOk()
            ->assertJsonPath('results.0.code', 'FR-2')
            ->assertJsonPath('results.0.title', 'Attach files');

        $this->getJson(route('quick.search', ['q' => 'ticket']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Create ticket']);
    }

    public function test_inline_update_changes_only_the_status_and_keeps_links(): void
    {
        $this->actingAs($this->user(EntityAccess::SUPER_ADMIN_SLUG));
        $agreed = EntityStatus::id(EntityStatus::AGREED);

        $this->patchJson(route('quick.update', ['model' => 'FunctionalRequirement', 'id' => $this->fr->id]), ['field' => 'status_id', 'value' => $agreed])
            ->assertOk();

        $this->fr->refresh();
        $this->assertSame($agreed, (int) $this->fr->status_id);
        $this->assertNotNull($this->fr->stakeholder_need_id, 'Parent link must survive a quick edit.');
    }

    public function test_bulk_update_and_validation(): void
    {
        $this->actingAs($this->user(EntityAccess::SUPER_ADMIN_SLUG));
        $agreed = EntityStatus::id(EntityStatus::AGREED);

        $this->postJson(route('quick.bulk', ['model' => 'FunctionalRequirement']), ['field' => 'status_id', 'value' => $agreed, 'ids' => [$this->fr->id, $this->fr2->id]])
            ->assertOk()->assertJsonPath('updated', 2);

        $this->postJson(route('quick.bulk', ['model' => 'FunctionalRequirement']), ['field' => 'title', 'value' => 1, 'ids' => [$this->fr->id]])
            ->assertStatus(422);
        $this->postJson(route('quick.bulk', ['model' => 'FunctionalRequirement']), ['field' => 'status_id', 'value' => 999999, 'ids' => [$this->fr->id]])
            ->assertStatus(422);
    }

    public function test_view_only_role_cannot_quick_update(): void
    {
        $this->actingAs($this->user('viewer', ['FunctionalRequirement' => ['can_view' => true, 'can_create' => false, 'can_update' => false, 'can_delete' => false]]));

        $this->patchJson(route('quick.update', ['model' => 'FunctionalRequirement', 'id' => $this->fr->id]), ['field' => 'status_id', 'value' => EntityStatus::id(EntityStatus::AGREED)])
            ->assertForbidden();
    }

    public function test_list_page_shows_bulk_tools_and_palette(): void
    {
        $this->actingAs($this->user(EntityAccess::SUPER_ADMIN_SLUG));

        $this->get(model_route('FunctionalRequirement', 'index'))
            ->assertOk()
            ->assertSee('data-quick-options', false)
            ->assertSee('data-command-palette', false)
            ->assertSee('data-saved-views', false);
    }
}
