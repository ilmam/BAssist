<?php

namespace Tests\Unit;

use App\Models\Assumption;
use App\Models\BusinessRule;
use App\Models\Constraint;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\AssumptionRepository;
use App\Repositories\BusinessRuleRepository;
use App\Repositories\ConstraintRepository;
use App\Services\TenancyProvisioner;
use App\Support\AssumptionStatus;
use App\Support\BusinessRuleStatus;
use App\Support\ConstraintStatus;
use App\Support\ProjectContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rules & Assumptions lists must stay confined to the selected project.
 * A new empty project must not inherit another project's guardrails.
 */
class GuardrailProjectScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardrail_repositories_honor_project_list_scope(): void
    {
        $this->assertTrue((new AssumptionRepository)->usesProjectListScope());
        $this->assertTrue((new ConstraintRepository)->usesProjectListScope());
        $this->assertTrue((new BusinessRuleRepository)->usesProjectListScope());
    }

    public function test_guardrail_lists_do_not_leak_rows_from_another_project(): void
    {
        [$projectA, $projectB] = $this->seedTwoProjects();
        $this->actingAs($this->userForWorkspace($projectA->workspace_id));

        Assumption::query()->create([
            'project_id' => $projectA->id,
            'title' => 'Assumption on A',
            'status' => AssumptionStatus::OPEN,
        ]);
        Constraint::query()->create([
            'project_id' => $projectA->id,
            'title' => 'Constraint on A',
            'status' => ConstraintStatus::ACTIVE,
        ]);
        BusinessRule::query()->create([
            'project_id' => $projectA->id,
            'title' => 'Rule on A',
            'status' => BusinessRuleStatus::ACTIVE,
        ]);

        $assumptionRepo = new AssumptionRepository;
        $constraintRepo = new ConstraintRepository;
        $ruleRepo = new BusinessRuleRepository;

        $this->assertCount(1, $assumptionRepo->getAll(['project_id' => $projectA->id]));
        $this->assertCount(1, $constraintRepo->getAll(['project_id' => $projectA->id]));
        $this->assertCount(1, $ruleRepo->getAll(['project_id' => $projectA->id]));

        $this->assertCount(0, $assumptionRepo->getAll(['project_id' => $projectB->id]));
        $this->assertCount(0, $constraintRepo->getAll(['project_id' => $projectB->id]));
        $this->assertCount(0, $ruleRepo->getAll(['project_id' => $projectB->id]));
    }

    public function test_guardrail_select_options_are_scoped_to_sticky_project(): void
    {
        [$projectA, $projectB] = $this->seedTwoProjects();

        $assumption = Assumption::query()->create([
            'project_id' => $projectA->id,
            'title' => 'Assumption on A',
        ]);

        $repository = new AssumptionRepository;

        try {
            $this->stubProjectContext((int) $projectA->id);
            $options = $repository->getSelectOptions();
            $this->assertArrayHasKey($assumption->id, $options);
            $this->assertCount(1, $options);

            $this->stubProjectContext((int) $projectB->id);
            $this->assertCount(0, $repository->getSelectOptions());

            $this->stubProjectContext(null);
            $this->assertCount(0, $repository->getSelectOptions());
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
            'name' => 'Guardrail Scope A',
            'code' => 'GSA-'.uniqid(),
        ]);
        $projectB = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Guardrail Scope B',
            'code' => 'GSB-'.uniqid(),
        ]);

        return [$projectA, $projectB];
    }

    protected function userForWorkspace(int $workspaceId): User
    {
        $workspace = Workspace::query()->findOrFail($workspaceId);

        return User::factory()->create([
            'tenant_id' => $workspace->tenant_id,
            'workspace_id' => $workspace->id,
        ]);
    }

    protected function stubProjectContext(?int $projectId): void
    {
        $stub = $this->createStub(ProjectContext::class);
        $stub->method('id')->willReturn($projectId);
        $this->app->instance(ProjectContext::class, $stub);
    }
}
