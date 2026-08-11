<?php

namespace Tests\Unit;

use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\Feature;
use App\Models\Project;
use App\Models\Stakeholder;
use App\Models\StakeholderNeed;
use App\Models\SwimlaneFlow;
use App\Models\SwimlaneFlowStep;
use App\Repositories\BusinessNeedRepository;
use App\Repositories\BusinessObjectiveRepository;
use App\Repositories\FeatureRepository;
use App\Repositories\StakeholderNeedRepository;
use App\Repositories\StakeholderRepository;
use App\Repositories\SwimlaneFlowStepRepository;
use App\Services\TenancyProvisioner;
use App\Support\ProjectContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Parent / upstream form selects must stay confined to the sticky project.
 */
class ProjectScopedSelectOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stakeholder_need_select_options_are_scoped_to_sticky_project(): void
    {
        [$projectA, $projectB] = $this->seedTwoProjects();

        $needA = StakeholderNeed::query()->create([
            'project_id' => $projectA->id,
            'title' => 'Need A',
        ]);
        StakeholderNeed::query()->create([
            'project_id' => $projectB->id,
            'title' => 'Need B',
        ]);

        $repository = new StakeholderNeedRepository;

        try {
            $this->stubProjectContext((int) $projectA->id);
            $options = $repository->getSelectOptions();
            $this->assertArrayHasKey($needA->id, $options);
            $this->assertCount(1, $options);

            $this->stubProjectContext((int) $projectB->id);
            $this->assertArrayNotHasKey($needA->id, $repository->getSelectOptions());

            $this->stubProjectContext(null);
            $this->assertSame([], $repository->getSelectOptions());
        } finally {
            $this->app->forgetInstance(ProjectContext::class);
        }
    }

    public function test_swimlane_flow_step_select_options_are_scoped_to_sticky_project(): void
    {
        [$projectA, $projectB] = $this->seedTwoProjects();

        $flowA = SwimlaneFlow::query()->create([
            'project_id' => $projectA->id,
            'title' => 'Flow A',
        ]);
        $flowB = SwimlaneFlow::query()->create([
            'project_id' => $projectB->id,
            'title' => 'Flow B',
        ]);

        $stepA = SwimlaneFlowStep::query()->create([
            'swimlane_flow_id' => $flowA->id,
            'project_id' => $projectA->id,
            'position' => 0,
            'lane' => 'Ops',
            'type' => 'process',
            'label' => 'Step A',
        ]);
        SwimlaneFlowStep::query()->create([
            'swimlane_flow_id' => $flowB->id,
            'project_id' => $projectB->id,
            'position' => 0,
            'lane' => 'Ops',
            'type' => 'process',
            'label' => 'Step B',
        ]);

        $repository = new SwimlaneFlowStepRepository;

        try {
            $this->stubProjectContext((int) $projectA->id);
            $options = $repository->getSelectOptions()->all();
            $this->assertArrayHasKey('', $options);
            $this->assertArrayHasKey($stepA->id, $options);
            $this->assertCount(2, $options);

            $this->stubProjectContext((int) $projectB->id);
            $this->assertArrayNotHasKey($stepA->id, $repository->getSelectOptions()->all());

            $this->stubProjectContext(null);
            $empty = $repository->getSelectOptions()->all();
            $this->assertSame(['' => ''], $empty);
        } finally {
            $this->app->forgetInstance(ProjectContext::class);
        }
    }

    public function test_base_project_owned_select_options_are_scoped_to_sticky_project(): void
    {
        [$projectA, $projectB] = $this->seedTwoProjects();

        $needA = BusinessNeed::query()->create([
            'project_id' => $projectA->id,
            'title' => 'BN A',
        ]);
        BusinessNeed::query()->create([
            'project_id' => $projectB->id,
            'title' => 'BN B',
        ]);

        $objectiveA = BusinessObjective::query()->create([
            'project_id' => $projectA->id,
            'title' => 'BO A',
        ]);
        $stakeholderA = Stakeholder::query()->create([
            'project_id' => $projectA->id,
            'name' => 'SH A',
        ]);
        $featureA = Feature::query()->create([
            'project_id' => $projectA->id,
            'title' => 'FE A',
            'stakeholder_need_id' => StakeholderNeed::query()->create([
                'project_id' => $projectA->id,
                'title' => 'SN for feature',
            ])->id,
        ]);

        try {
            $this->stubProjectContext((int) $projectA->id);

            $this->assertArrayHasKey($needA->id, (new BusinessNeedRepository)->getSelectOptions());
            $this->assertArrayHasKey($objectiveA->id, (new BusinessObjectiveRepository)->getSelectOptions());
            $this->assertArrayHasKey($stakeholderA->id, (new StakeholderRepository)->getSelectOptions());
            $this->assertArrayHasKey($featureA->id, (new FeatureRepository)->getSelectOptions());

            $this->stubProjectContext(null);
            $this->assertCount(0, (new BusinessNeedRepository)->getSelectOptions());
            $this->assertCount(0, (new BusinessObjectiveRepository)->getSelectOptions());
            $this->assertCount(0, (new StakeholderRepository)->getSelectOptions());
            $this->assertCount(0, (new FeatureRepository)->getSelectOptions());
        } finally {
            $this->app->forgetInstance(ProjectContext::class);
        }
    }

    /**
     * @return array{0: Project, 1: Project}
     */
    protected function seedTwoProjects(): array
    {
        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);

        $projectA = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Select Scope A',
            'code' => 'SSA-'.uniqid(),
        ]);
        $projectB = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Select Scope B',
            'code' => 'SSB-'.uniqid(),
        ]);

        return [$projectA, $projectB];
    }

    protected function stubProjectContext(?int $projectId): void
    {
        $stub = $this->createStub(ProjectContext::class);
        $stub->method('id')->willReturn($projectId);
        $this->app->instance(ProjectContext::class, $stub);
    }
}
