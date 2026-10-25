<?php

namespace Tests\Feature;

use App\Livewire\EntityForm;
use App\Models\Project;
use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The create / edit form as a Livewire component (docs/livewire.md): the same
 * rules, tenant guard and permissions as the controller.
 */
class EntityFormLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected Project $project;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $tenant = Tenant::query()->create(['name' => 'Acme', 'slug' => 'acme']);
        $workspace = Workspace::query()->create(['tenant_id' => $tenant->id, 'name' => 'Main', 'slug' => 'main']);
        $this->project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Portal', 'code' => 'POR']);

        $role = Role::query()->create(['name' => 'Analyst', 'slug' => 'analyst']);
        RoleEntityPermission::query()->create([
            'role_id' => $role->id, 'entity' => 'Risk',
            'can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true,
        ]);

        $this->user = User::factory()->create(['role_id' => $role->id, 'tenant_id' => $tenant->id, 'workspace_id' => $workspace->id]);
        $this->actingAs($this->user);
    }

    public function test_a_field_is_validated_when_the_user_leaves_it(): void
    {
        Livewire::test(EntityForm::class, ['model' => 'Risk', 'values' => []])
            ->call('validateField', 'title', '')
            ->assertHasErrors(['title'])
            ->call('validateField', 'title', 'Supplier delay')
            ->assertHasNoErrors(['title']);
    }

    public function test_a_user_without_the_permission_cannot_open_the_component(): void
    {
        Livewire::test(EntityForm::class, ['model' => 'StakeholderNeed'])->assertForbidden();
    }

    public function test_entities_with_their_own_controller_keep_the_classic_form(): void
    {
        $this->assertFalse(EntityForm::supports(['title' => 'text'], 'Feature'));
        $this->assertTrue(EntityForm::supports(['title' => 'text'], 'Risk'));
    }

    public function test_the_classic_form_is_used_when_livewire_forms_are_switched_off(): void
    {
        config(['ui.livewire_forms' => false]);

        $this->assertFalse(EntityForm::supports(['title' => 'text'], 'Risk'));
    }
}
