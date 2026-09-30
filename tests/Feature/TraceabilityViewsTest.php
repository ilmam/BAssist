<?php

namespace Tests\Feature;

use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\ChangeRequest;
use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\Role;
use App\Models\StakeholderNeed;
use App\Models\User;
use App\Services\TenancyProvisioner;
use App\Services\TraceabilityGraphService;
use App\Services\TraceabilityMatrixService;
use App\Support\EntityAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraceabilityViewsTest extends TestCase
{
    use RefreshDatabase;

    protected Project $project;

    protected StakeholderNeed $story;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $role = Role::query()->create(['name' => 'Super Admin', 'slug' => EntityAccess::SUPER_ADMIN_SLUG]);
        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);
        $this->actingAs(User::factory()->create(['role_id' => $role->id, 'tenant_id' => $tenant->id, 'workspace_id' => $workspace->id]));

        $this->project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Trace', 'code' => 'TRC']);
        $need = BusinessNeed::query()->create(['project_id' => $this->project->id, 'title' => 'Need "quoted" <b>']);
        $objective = BusinessObjective::query()->create(['project_id' => $this->project->id, 'title' => 'Objective']);
        $objective->businessNeeds()->sync([$need->id => ['is_primary' => true]]);
        $this->story = StakeholderNeed::query()->create(['project_id' => $this->project->id, 'title' => 'Story']);
        $this->story->businessObjectives()->attach($objective->id);
        FunctionalRequirement::query()->create(['project_id' => $this->project->id, 'stakeholder_need_id' => $this->story->id, 'title' => 'FR one', 'statement' => 'x']);
        FunctionalRequirement::query()->create(['project_id' => $this->project->id, 'title' => 'Orphan FR', 'statement' => 'y']);
    }

    public function test_graph_escapes_labels_links_nodes_and_marks_orphans(): void
    {
        $rows = app(TraceabilityMatrixService::class)->build(['project_id' => $this->project->id])['rows'];
        $graph = app(TraceabilityGraphService::class)->graph($rows);

        $this->assertStringStartsWith('flowchart LR', $graph['mermaid']);
        $this->assertStringContainsString('#quot;quoted#quot; #lt;b#gt;', $graph['mermaid']);
        $this->assertArrayHasKey('SN'.$this->story->id, $graph['links']);
        $this->assertStringContainsString(':::gap', $graph['mermaid']);
        $this->assertFalse($graph['too_large']);
    }

    public function test_coverage_counts_levels(): void
    {
        $rows = app(TraceabilityMatrixService::class)->build(['project_id' => $this->project->id])['rows'];
        $coverage = collect(app(TraceabilityGraphService::class)->coverage($rows))->keyBy('key');

        $this->assertSame(100, $coverage['solution']['pct']);
        $this->assertLessThan(100, $coverage['stakeholder_need']['pct']);
    }

    public function test_table_and_graph_views_render(): void
    {
        $this->get(route('traceability.index', ['project_id' => $this->project->id]))
            ->assertOk()->assertSee(__('ui.trace_coverage_heading'))->assertSee('ba-segmented', false);

        $this->get(route('traceability.index', ['project_id' => $this->project->id, 'view' => 'graph']))
            ->assertOk()->assertSee('data-trace-graph-source', false);
    }

    public function test_change_request_impact_groups_items(): void
    {
        $cr = ChangeRequest::query()->create([
            'project_id' => $this->project->id,
            'stakeholder_need_id' => $this->story->id,
            'title' => 'Change',
            'problem' => 'p',
            'proposed_change' => 'c',
        ]);

        $this->get(model_route('ChangeRequest', 'show', $cr->id))
            ->assertOk()
            ->assertSee(trans_choice('ui.cr_impact_total', 1, ['count' => 1]))
            ->assertSee('FR one');
    }
}
