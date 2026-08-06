<?php

namespace Tests\Unit;

use App\Models\ChangeRequest;
use App\Models\Project;
use App\Models\StakeholderNeed;
use App\Services\TenancyProvisioner;
use App\Support\ChangeRequestStatus;
use App\Support\SolutionPackagingParent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SolutionPackagingParentTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_stakeholder_need(): void
    {
        $this->expectException(ValidationException::class);

        SolutionPackagingParent::normalize([
            'stakeholder_need_id' => null,
            'change_request_id' => null,
        ]);
    }

    public function test_allows_stakeholder_need_only(): void
    {
        $data = SolutionPackagingParent::normalize([
            'stakeholder_need_id' => 3,
            'change_request_id' => null,
        ]);

        $this->assertSame(3, $data['stakeholder_need_id']);
        $this->assertNull($data['change_request_id']);
    }

    public function test_allows_stakeholder_need_with_matching_change_request(): void
    {
        [$need, $cr] = $this->seedNeedAndApprovedCr();

        $data = SolutionPackagingParent::normalize([
            'stakeholder_need_id' => $need->id,
            'change_request_id' => $cr->id,
        ]);

        $this->assertSame($need->id, $data['stakeholder_need_id']);
        $this->assertSame($cr->id, $data['change_request_id']);
    }

    public function test_fills_stakeholder_need_from_change_request_when_missing(): void
    {
        [$need, $cr] = $this->seedNeedAndApprovedCr();

        $data = SolutionPackagingParent::normalize([
            'stakeholder_need_id' => null,
            'change_request_id' => $cr->id,
        ]);

        $this->assertSame($need->id, $data['stakeholder_need_id']);
        $this->assertSame($cr->id, $data['change_request_id']);
    }

    public function test_rejects_change_request_on_different_stakeholder_need(): void
    {
        [$need, $cr] = $this->seedNeedAndApprovedCr();
        $otherNeed = StakeholderNeed::query()->create([
            'project_id' => $need->project_id,
            'title' => 'Other need',
        ]);

        $this->expectException(ValidationException::class);

        SolutionPackagingParent::normalize([
            'stakeholder_need_id' => $otherNeed->id,
            'change_request_id' => $cr->id,
        ]);
    }

    /**
     * @return array{0: StakeholderNeed, 1: ChangeRequest}
     */
    protected function seedNeedAndApprovedCr(): array
    {
        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);

        $project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Packaging Parent Test',
            'code' => 'PPT-'.uniqid(),
        ]);

        $need = StakeholderNeed::query()->create([
            'project_id' => $project->id,
            'title' => 'Anchored need',
        ]);

        $cr = ChangeRequest::query()->create([
            'project_id' => $project->id,
            'title' => 'Approved change',
            'problem' => 'Problem',
            'proposed_change' => 'Change',
            'stakeholder_need_id' => $need->id,
            'status' => ChangeRequestStatus::APPROVED,
        ]);

        return [$need, $cr];
    }
}
