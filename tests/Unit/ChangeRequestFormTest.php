<?php

namespace Tests\Unit;

use App\Data\ChangeRequestData;
use App\Http\Controllers\ChangeRequestController;
use App\Models\ChangeRequest;
use App\Models\Project;
use App\Models\StakeholderNeed;
use App\Repositories\ChangeRequestRepository;
use App\Services\TenancyProvisioner;
use App\Support\ChangeRequestImpact;
use App\Support\ChangeRequestStatus;
use App\Support\CrudEntityRegistry;
use App\Support\EntityFormBuilder;
use App\Support\EntityStatus;
use App\Support\ProjectContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ChangeRequestFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_entity_is_registered_for_crud(): void
    {
        $this->assertContains('ChangeRequest', array_keys(CrudEntityRegistry::all()));
    }

    public function test_entity_number_prefix_is_cr(): void
    {
        $method = new \ReflectionMethod(ChangeRequest::class, 'entityNumberPrefix');

        $this->assertSame('CR', $method->invoke(null));
    }

    public function test_form_uses_stakeholder_need_anchor(): void
    {
        $fields = (new EntityFormBuilder)->fields(ChangeRequestData::class);

        $this->assertSame('text', $fields['requestor']['type'] ?? null);
        $this->assertSame('select', $fields['impact_level']['type'] ?? null);
        $this->assertSame('select', $fields['stakeholder_need_id']['type'] ?? null);
        $this->assertArrayNotHasKey('affected_type', $fields);
        $this->assertArrayNotHasKey('affected_id', $fields);
        $this->assertEqualsCanonicalizing(
            ChangeRequestImpact::values(),
            array_keys($fields['impact_level']['list'] ?? [])
        );
    }

    public function test_validation_requires_core_intake_fields(): void
    {
        $rules = ChangeRequestData::rules();

        $this->assertContains('required', $rules['problem']);
        $this->assertContains('required', $rules['proposed_change']);
        $this->assertContains('required', $rules['requestor']);
        $this->assertContains('required', $rules['impact_level']);
    }

    public function test_review_statuses_require_stakeholder_need(): void
    {
        $this->assertContains(ChangeRequestStatus::UNDER_REVIEW, ChangeRequestStatus::requiresStakeholderNeed());
        $this->assertNotContains(ChangeRequestStatus::DRAFT, ChangeRequestStatus::requiresStakeholderNeed());
    }

    public function test_need_revision_is_entity_status(): void
    {
        $this->assertContains(EntityStatus::NEED_REVISION, EntityStatus::values());
    }

    public function test_create_form_prefills_stakeholder_need_from_query(): void
    {
        $request = Request::create('/change_requests/modal/create', 'GET', [
            'project_id' => 5,
            'stakeholder_need_id' => 12,
        ]);
        $this->app->instance('request', $request);

        $controller = app(ChangeRequestController::class);
        $method = new \ReflectionMethod(ChangeRequestController::class, 'applyStickyContextDefaults');
        $method->setAccessible(true);

        $dto = $method->invoke(
            $controller,
            ChangeRequestData::from(ChangeRequestData::empty())
        );

        $this->assertSame(5, $dto->project_id);
        $this->assertSame(12, $dto->stakeholder_need_id);
    }

    /**
     * FR/Feature "Change Request" select must only offer approved/implemented
     * CRs from the sticky project in scope — never another project's CRs, and
     * never draft/under_review/rejected ones.
     */
    public function test_select_options_are_scoped_to_current_project_and_approved_status(): void
    {
        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);

        $project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'CR Select Scope',
            'code' => 'CRS-'.uniqid(),
        ]);
        $otherProject = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'CR Select Other',
            'code' => 'CRO-'.uniqid(),
        ]);

        $need = StakeholderNeed::query()->create([
            'project_id' => $project->id,
            'title' => 'Anchored need',
        ]);

        $approved = ChangeRequest::query()->create([
            'project_id' => $project->id,
            'title' => 'Approved change',
            'problem' => 'Problem',
            'proposed_change' => 'Change',
            'stakeholder_need_id' => $need->id,
            'status' => ChangeRequestStatus::APPROVED,
        ]);
        ChangeRequest::query()->create([
            'project_id' => $project->id,
            'title' => 'Draft change',
            'problem' => 'Problem',
            'proposed_change' => 'Change',
            'stakeholder_need_id' => $need->id,
            'status' => ChangeRequestStatus::DRAFT,
        ]);
        ChangeRequest::query()->create([
            'project_id' => $otherProject->id,
            'title' => 'Other project approved',
            'problem' => 'Problem',
            'proposed_change' => 'Change',
            'status' => ChangeRequestStatus::APPROVED,
        ]);

        $repository = new ChangeRequestRepository;

        try {
            $stub = $this->createStub(ProjectContext::class);
            $stub->method('id')->willReturn((int) $project->id);
            $this->app->instance(ProjectContext::class, $stub);
            $options = $repository->getSelectOptions();
            $this->assertArrayHasKey($approved->id, $options);
            $this->assertCount(2, $options); // blank + approved

            $otherStub = $this->createStub(ProjectContext::class);
            $otherStub->method('id')->willReturn((int) $otherProject->id);
            $this->app->instance(ProjectContext::class, $otherStub);
            $this->assertArrayNotHasKey($approved->id, $repository->getSelectOptions());

            $noProjectStub = $this->createStub(ProjectContext::class);
            $noProjectStub->method('id')->willReturn(null);
            $this->app->instance(ProjectContext::class, $noProjectStub);
            $this->assertSame(['' => ''], $repository->getSelectOptions());
        } finally {
            $this->app->forgetInstance(ProjectContext::class);
        }
    }
}
