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

    public function test_requires_at_least_one_parent(): void
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

    public function test_allows_change_request_only(): void
    {
        [$need, $cr] = $this->seedNeedAndApprovedCr();

        $data = SolutionPackagingParent::normalize([
            'stakeholder_need_id' => null,
            'change_request_id' => $cr->id,
        ]);

        $this->assertNull($data['stakeholder_need_id']);
        $this->assertSame($cr->id, $data['change_request_id']);
        $this->assertSame($need->id, $cr->stakeholder_need_id);
    }

    public function test_rejects_both_parents(): void
    {
        [$need, $cr] = $this->seedNeedAndApprovedCr();

        $this->expectException(ValidationException::class);

        SolutionPackagingParent::normalize([
            'stakeholder_need_id' => $need->id,
            'change_request_id' => $cr->id,
        ]);
    }

    public function test_rejects_unapproved_change_request(): void
    {
        [$need, $cr] = $this->seedNeedAndApprovedCr();
        $cr->update(['status' => ChangeRequestStatus::DRAFT]);

        $this->expectException(ValidationException::class);

        SolutionPackagingParent::normalize([
            'stakeholder_need_id' => null,
            'change_request_id' => $cr->id,
        ]);
    }

    public function test_rejects_change_request_without_stakeholder_need_anchor(): void
    {
        [, $cr] = $this->seedNeedAndApprovedCr();
        $cr->update(['stakeholder_need_id' => null]);

        $this->expectException(ValidationException::class);

        SolutionPackagingParent::normalize([
            'stakeholder_need_id' => null,
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
