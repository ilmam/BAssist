<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\Role;
use App\Models\StakeholderNeed;
use App\Models\Status;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\TenancyProvisioner;
use App\Support\AttachableSupport;
use App\Support\EntityAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_requirement_types_are_attachable_and_status_is_not(): void
    {
        $this->assertTrue(AttachableSupport::enabled('FunctionalRequirement'));
        $this->assertTrue(AttachableSupport::enabled('StakeholderNeed'));
        $this->assertTrue(AttachableSupport::enabled('Feature'));
        $this->assertFalse(AttachableSupport::enabled('Status'));
        $this->assertFalse(entity_attachable('Priority'));
    }

    public function test_upload_download_and_remove_on_functional_requirement(): void
    {
        Storage::fake('local');
        $user = $this->actingSuperAdmin();
        $requirement = $this->seedFunctionalRequirement();
        $file = UploadedFile::fake()->create('spec.pdf', 120, 'application/pdf');

        $this->actingAs($user)
            ->post(route('functional_requirements.attachments.store', $requirement->id), [
                'file' => $file,
            ])
            ->assertRedirect(route('functional_requirements.show', $requirement->id));

        $attachment = Attachment::query()->first();
        $this->assertNotNull($attachment);
        $this->assertSame('spec.pdf', $attachment->original_name);
        $this->assertSame(FunctionalRequirement::class, $attachment->attachable_type);
        $this->assertTrue(Storage::disk('local')->exists($attachment->path));

        $this->get(route('functional_requirements.attachments.show', [$requirement->id, $attachment->id]))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->delete(route('functional_requirements.attachments.destroy', [$requirement->id, $attachment->id]))
            ->assertRedirect(route('functional_requirements.show', $requirement->id));

        $this->assertSoftDeleted($attachment);
    }

    public function test_rejects_disallowed_extension(): void
    {
        Storage::fake('local');
        $user = $this->actingSuperAdmin();
        $requirement = $this->seedFunctionalRequirement();

        $this->actingAs($user)
            ->from(route('functional_requirements.show', $requirement->id))
            ->post(route('functional_requirements.attachments.store', $requirement->id), [
                'file' => UploadedFile::fake()->create('payload.exe', 20, 'application/octet-stream'),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Attachment::query()->count());
    }

    public function test_store_on_non_attachable_entity_is_not_found(): void
    {
        Storage::fake('local');
        $user = $this->actingSuperAdmin();
        $status = Status::query()->create([
            'name' => 'Draft',
            'code' => 'draft-att-test',
        ]);

        $this->actingAs($user)
            ->post(route('statuses.attachments.store', $status->id), [
                'file' => UploadedFile::fake()->create('note.txt', 10, 'text/plain'),
            ])
            ->assertNotFound();
    }

    public function test_ability_mapping_for_attachment_actions(): void
    {
        $this->assertSame(EntityAccess::UPDATE, EntityAccess::abilityForRouteAction('attachments.store'));
        $this->assertSame(EntityAccess::UPDATE, EntityAccess::abilityForRouteAction('attachments.destroy'));
        $this->assertSame(EntityAccess::VIEW, EntityAccess::abilityForRouteAction('attachments.show'));
        $this->assertSame(EntityAccess::UPDATE, EntityAccess::abilityForControllerMethod('storeAttachment'));
        $this->assertSame(EntityAccess::VIEW, EntityAccess::abilityForControllerMethod('showAttachment'));
    }

    public function test_service_lists_files_for_the_parent(): void
    {
        Storage::fake('local');
        $requirement = $this->seedFunctionalRequirement();
        $stored = app(AttachmentService::class)->store(
            'FunctionalRequirement',
            $requirement->id,
            UploadedFile::fake()->create('brief.md', 8, 'text/markdown')
        );

        $listed = app(AttachmentService::class)->list('FunctionalRequirement', $requirement->id);

        $this->assertCount(1, $listed);
        $this->assertSame($stored->id, $listed[0]->id);
    }

    protected function actingSuperAdmin(): User
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => EntityAccess::SUPER_ADMIN_SLUG,
        ]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    protected function seedFunctionalRequirement(): FunctionalRequirement
    {
        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);
        $project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Attachments',
            'code' => 'ATT-'.uniqid(),
        ]);
        $story = StakeholderNeed::query()->create([
            'project_id' => $project->id,
            'title' => 'Dealer can attach evidence',
        ]);

        return FunctionalRequirement::query()->create([
            'project_id' => $project->id,
            'stakeholder_need_id' => $story->id,
            'title' => 'Accept PDF evidence',
            'statement' => 'The system shall accept a PDF attachment.',
        ]);
    }
}
