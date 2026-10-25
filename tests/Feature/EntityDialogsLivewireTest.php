<?php

namespace Tests\Feature;

use App\Livewire\EntityDelete;
use App\Livewire\EntityView;
use App\Models\Project;
use App\Models\Risk;
use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The delete confirmation and the view pop-up as Livewire components
 * (docs/livewire.md), with the tenant scope of the entities they show.
 */
class EntityDialogsLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected Risk $risk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $tenant = Tenant::query()->create(['name' => 'Acme', 'slug' => 'acme']);
        $workspace = Workspace::query()->create(['tenant_id' => $tenant->id, 'name' => 'Main', 'slug' => 'main']);
        $project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Portal', 'code' => 'POR']);

        $role = Role::query()->create(['name' => 'Analyst', 'slug' => 'analyst']);
        RoleEntityPermission::query()->create([
            'role_id' => $role->id, 'entity' => 'Risk',
            'can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true,
        ]);

        $this->actingAs(User::factory()->create(['role_id' => $role->id, 'tenant_id' => $tenant->id, 'workspace_id' => $workspace->id]));
        $this->risk = Risk::query()->create(['project_id' => $project->id, 'title' => 'Supplier delay']);
    }

    public function test_delete_removes_the_record_and_asks_the_page_to_refresh(): void
    {
        Livewire::test(EntityDelete::class, ['model' => 'Risk', 'recordId' => $this->risk->id])
            ->assertSee('Supplier delay')
            ->call('delete')
            ->assertDispatched('entity-saved');

        $this->assertNull(Risk::query()->find($this->risk->id));
    }

    public function test_deleting_a_record_someone_else_already_deleted_is_explained(): void
    {
        $component = Livewire::test(EntityDelete::class, ['model' => 'Risk', 'recordId' => $this->risk->id]);
        $this->risk->delete();

        $component->call('delete')
            ->assertDispatched('entity-saved')
            ->assertSee(__('livewire.record_gone'));
    }

    public function test_a_record_of_another_tenant_is_not_shown(): void
    {
        $other = Tenant::query()->create(['name' => 'Other', 'slug' => 'other']);
        $workspace = Workspace::query()->create(['tenant_id' => $other->id, 'name' => 'Else', 'slug' => 'else']);
        $project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Hidden', 'code' => 'HID']);
        $foreign = Risk::query()->withoutGlobalScopes()->create(['project_id' => $project->id, 'title' => 'Secret risk']);

        Livewire::test(EntityView::class, ['model' => 'Risk', 'recordId' => $foreign->id])
            ->assertDontSee('Secret risk')
            ->assertSee(__('livewire.record_gone'));
    }

    public function test_the_view_refreshes_only_when_the_record_changed(): void
    {
        $component = Livewire::test(EntityView::class, ['model' => 'Risk', 'recordId' => $this->risk->id])
            ->assertSee('Supplier delay');

        $component->call('poll')->assertSet('version', (string) $this->risk->updated_at->getTimestamp());

        $this->risk->forceFill(['title' => 'Late supplier', 'updated_at' => now()->addMinute()])->save();

        $component->call('poll')->assertSee('Late supplier');
    }

    public function test_a_user_without_the_permission_cannot_open_the_dialogs(): void
    {
        Livewire::test(EntityDelete::class, ['model' => 'StakeholderNeed', 'recordId' => 1])->assertForbidden();
        Livewire::test(EntityView::class, ['model' => 'StakeholderNeed', 'recordId' => 1])->assertForbidden();
    }

    public function test_entities_with_their_own_controller_and_the_switch_keep_the_classic_pop_ups(): void
    {
        $this->assertTrue(EntityDelete::supports('Risk'));
        $this->assertFalse(EntityDelete::supports('Feature'));

        config(['ui.livewire.dialogs' => false]);

        $this->assertFalse(EntityView::supports('Risk'));
    }
}
