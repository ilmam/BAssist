<?php

namespace Tests\Feature;

use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\FunctionalRequirement;
use App\Models\NonFunctionalRequirement;
use App\Models\Project;
use App\Models\Role;
use App\Models\StakeholderNeed;
use App\Models\User;
use App\Services\SpineCascadeService;
use App\Services\TenancyProvisioner;
use App\Support\EntityAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LineageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $role = Role::query()->create(['name' => 'Super Admin', 'slug' => EntityAccess::SUPER_ADMIN_SLUG]);
        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);
        $this->user = User::factory()->create(['role_id' => $role->id, 'tenant_id' => $tenant->id, 'workspace_id' => $workspace->id]);
        $this->actingAs($this->user);

        $this->project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Lineage', 'code' => 'LIN']);
    }

    /**
     * @return array{0: StakeholderNeed}
     */
    protected function spine(): array
    {
        $need = BusinessNeed::query()->create(['project_id' => $this->project->id, 'title' => 'Slow inquiries']);
        $objective = BusinessObjective::query()->create(['project_id' => $this->project->id, 'title' => 'Faster answers']);
        $objective->businessNeeds()->sync([$need->id => ['is_primary' => true]]);
        $story = StakeholderNeed::query()->create(['project_id' => $this->project->id, 'title' => 'Dealer can submit inquiry']);
        $story->businessObjectives()->attach($objective->id);

        return [$story];
    }

    public function test_fr_without_acceptance_criteria_suggests_writing_them(): void
    {
        [$story] = $this->spine();
        $fr = FunctionalRequirement::query()->create([
            'project_id' => $this->project->id,
            'stakeholder_need_id' => $story->id,
            'title' => 'Create ticket',
            'statement' => 'The system shall create a ticket.',
        ]);

        $lineage = app(SpineCascadeService::class)->for('FunctionalRequirement', $fr->id)['lineage'];

        $states = array_column($lineage['steps'], 'state');
        $this->assertSame(['done', 'done', 'done', 'current', 'missing'], $states);
        $this->assertSame(__('ui.lineage_write_criteria'), $lineage['next']['action']['label']);
        $this->assertStringContainsString('BABOK 7.1', (string) $lineage['next']['why']);
        $this->assertContains(__('ui.lineage_quick_risk'), array_column($lineage['quick'], 'label'));
    }

    public function test_orphan_nfr_offers_only_nearest_parent_link(): void
    {
        $nfr = NonFunctionalRequirement::query()->create([
            'project_id' => $this->project->id,
            'title' => 'Role based access',
            'category' => 'security',
            'statement' => 'Access is role based.',
            'description' => 'Access is role based.',
            'acceptance_criteria' => 'Given a dealer, they cannot see other dealers’ tickets.',
        ]);

        $lineage = app(SpineCascadeService::class)->for('NonFunctionalRequirement', $nfr->id)['lineage'];

        $this->assertSame(['blocked', 'blocked', 'missing', 'current', 'done'], array_column($lineage['steps'], 'state'));
        $this->assertSame(__('ui.lineage_link_level_3'), $lineage['steps'][2]['action']['label']);
        $this->assertSame(__('ui.lineage_link_level_3'), $lineage['next']['action']['label']);
    }

    public function test_detail_page_and_modal_render_lineage(): void
    {
        [$story] = $this->spine();

        $this->get(model_route('StakeholderNeed', 'show', $story->id))
            ->assertOk()
            ->assertSee('ba-lineage__rail', false)
            ->assertSee(__('ui.lineage_next_step'));

        $this->withHeader('X-Modal-Request', '1')
            ->get(model_modal_path('StakeholderNeed', 'view', $story->id))
            ->assertOk()
            ->assertSee('ba-lineage-compact', false);
    }
}
