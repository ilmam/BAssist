<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\NonFunctionalRequirement;
use App\Models\Project;
use App\Models\Scenario;
use App\Models\StakeholderNeed;
use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ApprovalService;
use App\Support\EntityStatus;
use App\Support\NfrCategory;
use Database\Seeders\StatusPrioritySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Guardrail A3: nothing is agreed without a way to accept it. A feature needs a live scenario,
 * a requirement needs acceptance criteria, whichever route sets the status.
 */
class AgreementGateTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(StatusPrioritySeeder::class);
        EntityStatus::forgetCache();

        $tenant = Tenant::query()->create(['name' => 'Gate', 'slug' => 'gate']);
        $workspace = Workspace::query()->create(['tenant_id' => $tenant->id, 'name' => 'Gate ws', 'slug' => 'gate-ws']);
        $this->project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Gate project', 'code' => 'GAT']);
    }

    public function test_a_feature_without_a_scenario_cannot_be_agreed_but_can_once_it_has_one(): void
    {
        $feature = $this->feature();

        $this->assertAgreeRefused($feature, 'no scenario');
        $this->assertFalse($feature->fresh()->isAgreed());

        $this->scenario($feature);
        $feature->update(['status_id' => EntityStatus::id(EntityStatus::AGREED)]);

        $this->assertTrue($feature->fresh()->isAgreed());
    }

    public function test_a_deprecated_scenario_does_not_count(): void
    {
        $feature = $this->feature();
        $this->scenario($feature)->update(['status_id' => EntityStatus::id(EntityStatus::DEPRECATED)]);

        $this->assertAgreeRefused($feature, 'no scenario');
    }

    public function test_a_requirement_without_acceptance_criteria_cannot_be_agreed(): void
    {
        $need = StakeholderNeed::query()->create(['project_id' => $this->project->id, 'title' => 'Dealer asks']);

        $fr = FunctionalRequirement::query()->create([
            'project_id' => $this->project->id, 'stakeholder_need_id' => $need->id,
            'title' => 'Create ticket', 'statement' => 'The system shall create a ticket.',
        ]);
        $nfr = NonFunctionalRequirement::query()->create([
            'project_id' => $this->project->id, 'stakeholder_need_id' => $need->id,
            'title' => 'Answer fast', 'category' => NfrCategory::PERFORMANCE, 'description' => 'Responses are fast.', 'statement' => 'The system shall answer within 2 seconds.',
        ]);

        foreach ([$fr, $nfr] as $requirement) {
            $this->assertAgreeRefused($requirement, 'no acceptance criteria');

            $requirement->update(['acceptance_criteria' => 'A ticket exists after submit.']);
            $requirement->update(['status_id' => EntityStatus::id(EntityStatus::AGREED)]);
            $this->assertTrue($requirement->fresh()->isAgreed());
        }
    }

    public function test_approving_is_refused_before_a_decision_is_recorded(): void
    {
        $role = Role::query()->create(['name' => 'approver', 'slug' => 'approver']);
        RoleEntityPermission::query()->create([
            'role_id' => $role->id, 'entity' => 'Feature',
            'can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => false, 'can_approve' => true,
        ]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id, 'tenant_id' => $this->project->workspace->tenant_id]));
        $feature = $this->feature();

        try {
            app(ApprovalService::class)->approve($feature);
            $this->fail('Approval should have been refused.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('no scenario', $e->errors()['note'][0]);
        }

        $this->assertSame(0, \App\Models\Approval::query()->count(), 'no decision was recorded');
        $this->assertFalse($feature->fresh()->isAgreed());
    }

    private function assertAgreeRefused($record, string $reason): void
    {
        try {
            $record->update(['status_id' => EntityStatus::id(EntityStatus::AGREED)]);
            $this->fail('Agreeing should have been refused.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString($reason, $e->errors()['status_id'][0]);
        }
    }

    private function feature(): Feature
    {
        return Feature::query()->create([
            'project_id' => $this->project->id,
            'title' => 'Quotes',
            'body' => "Feature: Quotes\n",
        ]);
    }

    private function scenario(Feature $feature): Scenario
    {
        return Scenario::query()->create([
            'feature_id' => $feature->id,
            'title' => 'Quote',
            'body' => "Scenario: Quote\n  Given a dealer\n  When they ask\n  Then they are answered\n",
        ]);
    }
}
