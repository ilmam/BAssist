<?php

namespace Tests\Feature;

use App\Models\Assumption;
use App\Models\BusinessRule;
use App\Models\Constraint;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\TenancyProvisioner;
use App\Support\AssumptionStatus;
use App\Support\BusinessRuleStatus;
use App\Support\ConstraintStatus;
use App\Support\EntityAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardrailHubScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardrail_datatable_api_does_not_leak_other_project_rows(): void
    {
        $user = $this->actingSuperAdmin();
        [$projectA, $projectB] = $this->seedTwoProjectsFor($user);

        Assumption::query()->create([
            'project_id' => $projectA->id,
            'title' => 'First Assumption from other project',
            'status' => AssumptionStatus::OPEN,
        ]);
        Constraint::query()->create([
            'project_id' => $projectA->id,
            'title' => 'NFR-01 from other project',
            'status' => ConstraintStatus::ACTIVE,
        ]);
        BusinessRule::query()->create([
            'project_id' => $projectA->id,
            'title' => 'BR-01 from other project',
            'status' => BusinessRuleStatus::ACTIVE,
        ]);

        $this->actingAs($user);

        foreach ([
            'api.assumption.index' => 'First Assumption from other project',
            'api.constraint.index' => 'NFR-01 from other project',
            'api.business_rule.index' => 'BR-01 from other project',
        ] as $route => $leakedTitle) {
            $this->getJson(route($route, ['project_id' => $projectB->id]))
                ->assertOk()
                ->assertJsonPath('recordsTotal', 0)
                ->assertJsonMissing(['title' => $leakedTitle]);
        }
    }

    protected function actingSuperAdmin(): User
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

    /**
     * @return array{0: Project, 1: Project}
     */
    protected function seedTwoProjectsFor(User $user): array
    {
        $projectA = Project::query()->create([
            'workspace_id' => $user->workspace_id,
            'name' => 'Existing Project',
            'code' => 'EXIST-'.uniqid(),
        ]);
        $projectB = Project::query()->create([
            'workspace_id' => $user->workspace_id,
            'name' => 'Used Cars Auction',
            'code' => 'UCA-'.uniqid(),
        ]);

        return [$projectA, $projectB];
    }
}
