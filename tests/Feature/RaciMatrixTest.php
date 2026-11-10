<?php

namespace Tests\Feature;

use App\Data\StakeholderData;
use App\Models\Project;
use App\Models\Role;
use App\Models\Stakeholder;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\RaciAssignmentRepository;
use App\Repositories\RaciItemRepository;
use App\Support\CrudEntityRegistry;
use App\Support\DtoMetadata;
use App\Support\RaciRole;
use App\Support\StakeholderType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * BABOK §10.43: stakeholder onion layers and the responsibility (RACI) matrix.
 */
class RaciMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::query()->create(['name' => 'Acme', 'slug' => 'acme']);
        $workspace = Workspace::query()->create(['tenant_id' => $tenant->id, 'name' => 'Main', 'slug' => 'main']);
        $role = Role::query()->create(['name' => 'Analyst', 'slug' => 'analyst']);
        $this->actingAs(User::factory()->create(['role_id' => $role->id, 'tenant_id' => $tenant->id, 'workspace_id' => $workspace->id]));

        $this->project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Platform', 'code' => 'PLT']);
    }

    protected function stakeholder(string $name, ?Project $project = null): Stakeholder
    {
        return Stakeholder::query()->create(['name' => $name, 'project_id' => ($project ?? $this->project)->id]);
    }

    protected function item(string $title, ?Project $project = null)
    {
        return (new RaciItemRepository())->create(['title' => $title, 'project_id' => ($project ?? $this->project)->id]);
    }

    protected function assign($item, Stakeholder $stakeholder, string $role, ?Project $project = null)
    {
        return (new RaciAssignmentRepository())->create([
            'project_id' => ($project ?? $this->project)->id,
            'raci_item_id' => $item->id,
            'stakeholder_id' => $stakeholder->id,
            'role' => $role,
        ]);
    }

    public function test_entities_are_registered_and_type_is_an_onion_layer_dropdown(): void
    {
        $this->assertContains('RaciItem', array_keys(CrudEntityRegistry::all()));
        $this->assertContains('RaciAssignment', array_keys(CrudEntityRegistry::all()));

        $fields = DtoMetadata::for(StakeholderData::class)->formFields();
        $this->assertSame('select', $fields['type'][0] ?? null);
        $this->assertSame('StakeholderType', $fields['type'][1] ?? null);
        $this->assertContains('domain_sme', array_keys(StakeholderType::selectOptions()));
    }

    public function test_a_full_row_of_roles_can_be_recorded(): void
    {
        $item = $this->item('Requirements sign-off');

        $this->assign($item, $this->stakeholder('Sponsor'), RaciRole::ACCOUNTABLE);
        $this->assign($item, $this->stakeholder('BA'), RaciRole::RESPONSIBLE);
        $this->assign($item, $this->stakeholder('SME'), RaciRole::CONSULTED);
        $this->assign($item, $this->stakeholder('Tester'), RaciRole::INFORMED);

        $this->assertCount(4, $item->fresh()->assignments);
    }

    public function test_only_one_stakeholder_can_be_accountable_per_item(): void
    {
        $item = $this->item('UAT');
        $this->assign($item, $this->stakeholder('Sponsor'), RaciRole::ACCOUNTABLE);

        $this->expectException(ValidationException::class);
        $this->assign($item, $this->stakeholder('PM'), RaciRole::ACCOUNTABLE);
    }

    public function test_changing_a_second_assignment_to_accountable_is_rejected(): void
    {
        $item = $this->item('UAT');
        $this->assign($item, $this->stakeholder('Sponsor'), RaciRole::ACCOUNTABLE);
        $second = $this->assign($item, $this->stakeholder('PM'), RaciRole::RESPONSIBLE);

        try {
            (new RaciAssignmentRepository())->update($second->id, ['role' => RaciRole::ACCOUNTABLE]);
            $this->fail('Second Accountable should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('role', $e->errors());
        }

        // Re-saving the existing Accountable itself is fine.
        $first = $item->assignments()->where('role', RaciRole::ACCOUNTABLE)->first();
        (new RaciAssignmentRepository())->update($first->id, ['role' => RaciRole::ACCOUNTABLE]);
        $this->assertSame(RaciRole::ACCOUNTABLE, $first->fresh()->role);
    }

    public function test_a_stakeholder_holds_one_role_per_item(): void
    {
        $item = $this->item('UAT');
        $pm = $this->stakeholder('PM');
        $this->assign($item, $pm, RaciRole::RESPONSIBLE);

        try {
            $this->assign($item, $pm, RaciRole::CONSULTED);
            $this->fail('Duplicate stakeholder on item should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('stakeholder_id', $e->errors());
        }
    }

    public function test_role_must_be_r_a_c_or_i(): void
    {
        $this->expectException(ValidationException::class);
        $this->assign($this->item('UAT'), $this->stakeholder('PM'), 'X');
    }

    public function test_item_and_stakeholder_must_belong_to_the_same_project(): void
    {
        $other = Project::query()->create(['workspace_id' => $this->project->workspace_id, 'name' => 'Other', 'code' => 'OTH']);
        $foreignItem = $this->item('Foreign', $other);

        try {
            $this->assign($foreignItem, $this->stakeholder('PM'), RaciRole::RESPONSIBLE);
            $this->fail('Cross-project item should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('raci_item_id', $e->errors());
        }
    }
}
