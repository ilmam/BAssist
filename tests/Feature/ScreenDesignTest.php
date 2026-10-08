<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Role;
use App\Models\Screen;
use App\Models\ScreenElement;
use App\Models\User;
use App\Services\TenancyProvisioner;
use App\Support\EntityAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Editing a screen through its element rows (docs/design-layer.md).
 */
class ScreenDesignTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create(['name' => 'Super Admin', 'slug' => EntityAccess::SUPER_ADMIN_SLUG]);
        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);

        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'tenant_id' => $tenant->id,
            'workspace_id' => $workspace->id,
        ]);
        $this->project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Design',
            'code' => 'DSG',
        ]);
    }

    public function test_updating_a_screen_from_the_form_payload_saves(): void
    {
        $screen = Screen::query()->create(['title' => 'Old', 'project_id' => $this->project->id]);

        $this->actingAs($this->user)
            ->putJson(route('screens.update', $screen->id), [
                'id' => $screen->id,
                'title' => 'New title',
                'project_id' => $this->project->id,
                'description' => 'About it',
            ])
            ->assertOk();

        $this->assertSame('New title', $screen->fresh()->title);
    }

    public function test_saving_the_form_replaces_the_element_rows_in_order(): void
    {
        $screen = Screen::query()->create(['title' => 'S', 'project_id' => $this->project->id]);
        $keep = ScreenElement::query()->create([
            'screen_id' => $screen->id, 'project_id' => $this->project->id,
            'position' => 0, 'kind' => 'label', 'label' => 'Keep me',
        ]);
        $drop = ScreenElement::query()->create([
            'screen_id' => $screen->id, 'project_id' => $this->project->id,
            'position' => 1, 'kind' => 'label', 'label' => 'Drop me',
        ]);

        $this->actingAs($this->user)
            ->putJson(route('screens.update', $screen->id), [
                'id' => $screen->id,
                'title' => 'S',
                'project_id' => $this->project->id,
                'elements' => [
                    '_present' => '1',
                    ['kind' => 'button', 'label' => 'New first', 'row' => '1'],
                    ['id' => $keep->id, 'kind' => 'label', 'label' => 'Kept and renamed', 'row' => '1'],
                    ['kind' => 'label', 'label' => '   '], // blank row: ignored
                ],
            ])
            ->assertOk();

        $rows = $screen->fresh()->screenElements;

        $this->assertSame(['New first', 'Kept and renamed'], $rows->pluck('label')->all());
        $this->assertSame([0, 1], $rows->pluck('position')->all());
        $this->assertSame($keep->id, $rows->last()->id, 'an existing row keeps its id');
        $this->assertNull(ScreenElement::query()->find($drop->id));
    }

    public function test_a_payload_without_rows_leaves_them_alone_and_an_empty_submit_removes_them(): void
    {
        $screen = Screen::query()->create(['title' => 'S', 'project_id' => $this->project->id]);
        ScreenElement::query()->create([
            'screen_id' => $screen->id, 'project_id' => $this->project->id,
            'position' => 0, 'kind' => 'label', 'label' => 'Stays',
        ]);
        $base = ['id' => $screen->id, 'title' => 'S', 'project_id' => $this->project->id];

        $this->actingAs($this->user)->putJson(route('screens.update', $screen->id), $base)->assertOk();
        $this->assertCount(1, $screen->fresh()->screenElements);

        $this->actingAs($this->user)
            ->putJson(route('screens.update', $screen->id), $base + ['elements' => ['_present' => '1']])
            ->assertOk();
        $this->assertCount(0, $screen->fresh()->screenElements);
    }

    public function test_preview_assembles_unsaved_rows_without_writing(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('screens.preview'), [
                'title' => 'Draft screen',
                'elements' => [
                    ['kind' => 'input', 'label' => 'Name'],
                    ['kind' => 'button', 'label' => 'Save', 'row' => '1'],
                    ['kind' => 'button', 'label' => 'Cancel', 'row' => '1'],
                ],
            ])
            ->assertOk();

        $salt = $response->json('salt');
        $this->assertStringContainsString('<b>Draft screen', $salt);
        $this->assertStringContainsString('[Save] | [Cancel]', $salt);
        $this->assertSame(0, Screen::query()->count());
        $this->assertSame(0, ScreenElement::query()->count());
    }

    public function test_the_edit_page_shows_the_row_editor_and_the_details_page_is_read_only(): void
    {
        $screen = Screen::query()->create(['title' => 'S', 'project_id' => $this->project->id]);
        ScreenElement::query()->create([
            'screen_id' => $screen->id, 'project_id' => $this->project->id,
            'position' => 0, 'kind' => 'label', 'label' => 'Hello there',
        ]);

        $this->actingAs($this->user)
            ->get(route('screens.edit', $screen->id))
            ->assertOk()
            ->assertSee('data-screen-designer', false)
            ->assertSee('name="elements[0][label]"', false)
            ->assertSee('elements[_present]', false);

        $this->actingAs($this->user)
            ->get(route('screens.show', $screen->id))
            ->assertOk()
            ->assertSee('Hello there')
            ->assertSee('data-element-list', false)
            ->assertDontSee('name="elements[0][label]"', false)
            ->assertDontSee('data-row-add', false);
    }

    public function test_nested_rows_are_saved_with_their_container_and_reload_with_keys(): void
    {
        $screen = Screen::query()->create(['title' => 'S', 'project_id' => $this->project->id]);

        $this->actingAs($this->user)
            ->putJson(route('screens.update', $screen->id), [
                'id' => $screen->id,
                'title' => 'S',
                'project_id' => $this->project->id,
                'elements' => [
                    '_present' => '1',
                    ['key' => 'n1', 'kind' => 'panel', 'label' => 'Box'],
                    ['key' => 'n2', 'parent_key' => 'n1', 'kind' => 'label', 'label' => 'Inside the box'],
                    ['key' => 'n3', 'kind' => 'button', 'label' => 'Outside'],
                ],
            ])
            ->assertOk();

        $rows = $screen->fresh()->screenElements->keyBy('label');
        $this->assertNull($rows['Box']->parent_id);
        $this->assertSame($rows['Box']->id, $rows['Inside the box']->parent_id);
        $this->assertNull($rows['Outside']->parent_id);

        // Reloaded for the form: keys are the stored ids, so a second save keeps the nesting.
        $edit = app(\App\Repositories\ScreenRepository::class)->editById($screen->id)->elements;
        $this->assertSame((string) $rows['Box']->id, $edit[1]['parent_key']);

        $this->actingAs($this->user)
            ->putJson(route('screens.update', $screen->id), [
                'id' => $screen->id,
                'title' => 'S',
                'project_id' => $this->project->id,
                'elements' => ['_present' => '1', ...$edit],
            ])
            ->assertOk();

        $again = $screen->fresh()->screenElements->keyBy('label');
        $this->assertSame($again['Box']->id, $again['Inside the box']->parent_id);
        $this->assertSame($rows['Box']->id, $again['Box']->id, 'ids are kept across saves');
    }

    public function test_deleting_a_container_removes_what_it_holds(): void
    {
        $screen = Screen::query()->create(['title' => 'S', 'project_id' => $this->project->id]);
        $box = ScreenElement::query()->create([
            'screen_id' => $screen->id, 'project_id' => $this->project->id,
            'position' => 0, 'kind' => 'panel', 'label' => 'Box',
        ]);
        ScreenElement::query()->create([
            'screen_id' => $screen->id, 'project_id' => $this->project->id, 'parent_id' => $box->id,
            'position' => 1, 'kind' => 'label', 'label' => 'Held',
        ]);

        $box->delete();

        $this->assertSame(0, ScreenElement::query()->where('screen_id', $screen->id)->count());
    }
}
