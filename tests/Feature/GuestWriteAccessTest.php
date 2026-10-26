<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\FunctionalRequirement;
use App\Models\User;
use App\Support\EntityStatus;
use App\Support\ProjectAccessLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SeedsProjectAccess;
use Tests\TestCase;

class GuestWriteAccessTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProjectAccess;

    /** @var array<string, mixed> */
    protected array $a;

    /** @var array<string, mixed> */
    protected array $a2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->a = $this->seedTenant('Alpha');
        $this->a2 = $this->seedSiblingProject($this->a['workspace'], 'Sibling');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function frPayload(array $a, array $overrides = []): array
    {
        return array_merge([
            'title' => 'Changed title',
            'project_id' => $a['project']->id,
            'stakeholder_need_id' => $a['need']->id,
            'change_request_id' => null,
            'statement' => 'The system shall be changed.',
            'acceptance_criteria' => 'Given a change',
        ], $overrides);
    }

    protected function guestWith(ProjectAccessLevel $level, ?array $project = null): User
    {
        $guest = User::factory()->guest()->create();
        $this->grant($guest, ($project ?? $this->a)['project'], $level);

        return $guest;
    }

    public function test_view_guest_cannot_update_create_or_delete(): void
    {
        $this->actingAs($this->guestWith(ProjectAccessLevel::View));

        $this->put(route('functional_requirements.update', $this->a['fr']->id), $this->frPayload($this->a))->assertForbidden();
        $this->post(route('functional_requirements.store'), $this->frPayload($this->a, ['title' => 'New']))->assertForbidden();
        $this->delete(route('functional_requirements.destroy', $this->a['fr']->id))->assertForbidden();

        $this->assertSame('Alpha requirement', FunctionalRequirement::withoutGlobalScopes()->find($this->a['fr']->id)->title);
        $this->assertNull(FunctionalRequirement::withoutGlobalScopes()->find($this->a['fr']->id)->deleted_at);
    }

    public function test_comment_guest_cannot_update_records(): void
    {
        $this->actingAs($this->guestWith(ProjectAccessLevel::Comment));

        $this->put(route('functional_requirements.update', $this->a['fr']->id), $this->frPayload($this->a))->assertForbidden();
    }

    public function test_edit_guest_can_update_create_and_delete_in_the_granted_project(): void
    {
        $this->actingAs($this->guestWith(ProjectAccessLevel::Edit));

        $this->put(route('functional_requirements.update', $this->a['fr']->id), $this->frPayload($this->a))->assertRedirect();
        $this->assertSame('Changed title', FunctionalRequirement::withoutGlobalScopes()->find($this->a['fr']->id)->title);

        $this->post(route('functional_requirements.store'), $this->frPayload($this->a, ['title' => 'Brand new']))->assertRedirect();
        $this->assertTrue(FunctionalRequirement::withoutGlobalScopes()->where('title', 'Brand new')->exists());

        $this->delete(route('functional_requirements.destroy', $this->a['fr']->id))->assertRedirect();
        $this->assertNotNull(FunctionalRequirement::withoutGlobalScopes()->find($this->a['fr']->id)->deleted_at);
    }

    public function test_edit_guest_cannot_move_a_record_into_a_view_only_project(): void
    {
        $guest = $this->guestWith(ProjectAccessLevel::Edit);
        $this->grant($guest, $this->a2['project'], ProjectAccessLevel::View);
        $this->actingAs($guest);

        $this->put(
            route('functional_requirements.update', $this->a['fr']->id),
            $this->frPayload($this->a, ['project_id' => $this->a2['project']->id, 'stakeholder_need_id' => $this->a2['need']->id]),
        )->assertForbidden();

        $this->assertSame($this->a['project']->id, (int) FunctionalRequirement::withoutGlobalScopes()->find($this->a['fr']->id)->project_id);
    }

    public function test_edit_guest_cannot_change_a_record_of_a_view_only_project(): void
    {
        $guest = $this->guestWith(ProjectAccessLevel::Edit);
        $this->grant($guest, $this->a2['project'], ProjectAccessLevel::View);
        $this->actingAs($guest);

        $this->put(
            route('functional_requirements.update', $this->a2['fr']->id),
            $this->frPayload($this->a2),
        )->assertForbidden();
        $this->delete(route('functional_requirements.destroy', $this->a2['fr']->id))->assertForbidden();

        $this->assertSame('Sibling requirement', FunctionalRequirement::withoutGlobalScopes()->find($this->a2['fr']->id)->title);
    }

    public function test_edit_guest_cannot_attach_files_to_a_view_only_project(): void
    {
        Storage::fake('local');
        $guest = $this->guestWith(ProjectAccessLevel::Edit);
        $this->grant($guest, $this->a2['project'], ProjectAccessLevel::View);
        $this->actingAs($guest);

        $this->post(
            route('functional_requirements.attachments.store', $this->a2['fr']->id),
            ['file' => UploadedFile::fake()->create('evidence.pdf', 80, 'application/pdf')],
        )->assertForbidden();
        $this->assertSame(0, Attachment::query()->count());

        $this->post(
            route('functional_requirements.attachments.store', $this->a['fr']->id),
            ['file' => UploadedFile::fake()->create('evidence.pdf', 80, 'application/pdf')],
        )->assertRedirect();
        $this->assertSame(1, Attachment::query()->count());
    }

    public function test_quick_edits_respect_the_project_of_each_record(): void
    {
        $this->seed(\Database\Seeders\StatusPrioritySeeder::class);
        EntityStatus::forgetCache();
        $guest = $this->guestWith(ProjectAccessLevel::Edit);
        $this->grant($guest, $this->a2['project'], ProjectAccessLevel::View);
        $this->actingAs($guest);
        $agreed = EntityStatus::id(EntityStatus::AGREED);

        $this->patchJson(route('quick.update', ['model' => 'FunctionalRequirement', 'id' => $this->a['fr']->id]), ['field' => 'status_id', 'value' => $agreed])->assertOk();
        $this->patchJson(route('quick.update', ['model' => 'FunctionalRequirement', 'id' => $this->a2['fr']->id]), ['field' => 'status_id', 'value' => $agreed])->assertForbidden();
        $this->postJson(route('quick.bulk', ['model' => 'FunctionalRequirement']), ['ids' => [$this->a2['fr']->id], 'field' => 'status_id', 'value' => $agreed])->assertForbidden();

        $this->assertSame($agreed, (int) FunctionalRequirement::withoutGlobalScopes()->find($this->a['fr']->id)->status_id);
        $this->assertNotSame($agreed, (int) FunctionalRequirement::withoutGlobalScopes()->find($this->a2['fr']->id)->status_id);
    }

    public function test_guest_cannot_approve_or_create_workspaces_or_edit_the_project(): void
    {
        $this->actingAs($this->guestWith(ProjectAccessLevel::Edit));

        $this->post(route('review.approve', ['model' => 'functional_requirement', 'id' => $this->a['fr']->id]))->assertForbidden();
        $this->get(route('workspaces.create'))->assertForbidden();
        $this->put(route('projects.update', $this->a['project']->id), ['name' => 'Renamed', 'workspace_id' => $this->a['workspace']->id])->assertForbidden();

        $this->assertSame('Alpha project', $this->a['project']->fresh()->name);
    }

    public function test_role_user_with_a_foreign_view_grant_cannot_write_there_even_with_a_write_role(): void
    {
        $b = $this->seedTenant('Bravo');
        $bravoUser = $this->analystIn($b['tenant'], $b['workspace']);
        $this->grant($bravoUser, $this->a['project'], ProjectAccessLevel::View);
        $this->actingAs($bravoUser);

        $this->put(route('functional_requirements.update', $this->a['fr']->id), $this->frPayload($this->a))->assertForbidden();
        $this->delete(route('functional_requirements.destroy', $this->a['fr']->id))->assertForbidden();
    }

    public function test_tenant_user_writes_in_own_tenant_are_unaffected(): void
    {
        $analyst = $this->analystIn($this->a['tenant'], $this->a['workspace']);
        $this->actingAs($analyst);

        $this->put(route('functional_requirements.update', $this->a['fr']->id), $this->frPayload($this->a))->assertRedirect();
        $this->assertSame('Changed title', FunctionalRequirement::withoutGlobalScopes()->find($this->a['fr']->id)->title);
    }
}
