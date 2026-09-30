<?php

namespace Tests\Feature;

use App\Models\BusinessNeed;
use App\Models\ChangeRequest;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\ProjectReadinessService;
use App\Services\TenancyProvisioner;
use App\Support\ChangeRequestStatus;
use App\Support\EntityAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAndReadinessUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_root_renders_home_page_with_empty_state_when_no_projects(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get('/')
            ->assertOk()
            ->assertSee(__('ui.home_no_projects_title'))
            ->assertSee(__('ui.home_awaiting_empty_title'));
    }

    public function test_home_lists_projects_awaiting_crs_and_recent_activity(): void
    {
        $user = $this->superAdmin();
        $project = Project::query()->create([
            'workspace_id' => $user->workspace_id,
            'name' => 'Used Cars Auction',
            'code' => 'UCA',
        ]);
        BusinessNeed::query()->create(['project_id' => $project->id, 'title' => 'Reduce auction cycle time']);
        ChangeRequest::query()->create([
            'project_id' => $project->id,
            'title' => 'Add reserve price',
            'problem' => 'Sellers need a floor price.',
            'proposed_change' => 'Add a reserve price field.',
            'status' => ChangeRequestStatus::UNDER_REVIEW,
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Used Cars Auction')
            ->assertSee('Add reserve price')
            ->assertSee('Reduce auction cycle time')
            ->assertSee('ba-badge--warning', false);
    }

    public function test_readiness_groups_checks_by_babok_folder(): void
    {
        $user = $this->superAdmin();
        $this->actingAs($user);
        $project = Project::query()->create([
            'workspace_id' => $user->workspace_id,
            'name' => 'Readiness',
            'code' => 'RDY',
        ]);
        BusinessNeed::query()->create(['project_id' => $project->id, 'title' => 'Orphan need']);

        $readiness = app(ProjectReadinessService::class)->forProject($project);

        $folderKeys = array_column($readiness['folders'], 'key');
        $this->assertSame(['strategy', 'radd', 'governance', 'evaluation'], $folderKeys);

        $gap = collect($readiness['items'])->firstWhere('key', 'needs_without_objective');
        $this->assertSame('strategy', $gap['folder']);

        $noRisks = collect($readiness['items'])->firstWhere('key', 'risks_captured');
        $this->assertNotNull($noRisks['fix_modal']);

        $strategy = collect($readiness['folders'])->firstWhere('key', 'strategy');
        $this->assertGreaterThan(0, $strategy['gaps']);
        $this->assertLessThan(100, $strategy['pct']);
    }

    public function test_project_dashboard_renders_folder_health_and_fix_actions(): void
    {
        $user = $this->superAdmin();
        $project = Project::query()->create([
            'workspace_id' => $user->workspace_id,
            'name' => 'Dashboard',
            'code' => 'DSH',
        ]);

        $this->actingAs($user)
            ->get(route('projects.dashboard', $project))
            ->assertOk()
            ->assertSee('readiness-strategy', false)
            ->assertSee('ba-folder-card', false)
            ->assertSee(__('ui.readiness_fix_add'));
    }

    public function test_status_tone_mapping_is_consistent(): void
    {
        $this->assertSame('success', ui_status_tone('Agreed'));
        $this->assertSame('warning', ui_status_tone('Need Revision'));
        $this->assertSame('warning', ui_status_tone('under_review'));
        $this->assertSame('neutral', ui_status_tone("Won't"));
        $this->assertSame('danger', ui_status_tone('Must'));
        $this->assertSame('neutral', ui_status_tone('Something custom'));
    }

    protected function superAdmin(): User
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => EntityAccess::SUPER_ADMIN_SLUG,
        ]);

        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);

        return User::factory()->create([
            'role_id' => $role->id,
            'tenant_id' => $tenant->id,
            'workspace_id' => $workspace->id,
        ]);
    }
}
