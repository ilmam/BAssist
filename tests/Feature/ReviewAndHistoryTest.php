<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Approval;
use App\Models\Comment;
use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Models\StakeholderNeed;
use App\Models\User;
use App\Services\TenancyProvisioner;
use App\Support\EntityAccess;
use App\Support\EntityStatus;
use App\Support\RolePermissionSync;
use Database\Seeders\StatusPrioritySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewAndHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected Project $project;

    protected FunctionalRequirement $fr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(StatusPrioritySeeder::class);
        EntityStatus::forgetCache();

        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);
        $this->project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Review', 'code' => 'REV']);
        $this->actingAs($this->user('owner', true));
        $story = StakeholderNeed::query()->create(['project_id' => $this->project->id, 'title' => 'Dealer submits inquiry']);
        $this->fr = FunctionalRequirement::query()->create([
            'project_id' => $this->project->id, 'stakeholder_need_id' => $story->id,
            'title' => 'Create ticket', 'statement' => 'The system shall create a ticket.',
        ]);
    }

    protected function user(string $slug, bool $canApprove): User
    {
        $role = Role::query()->create(['name' => $slug, 'slug' => $slug]);
        foreach (['FunctionalRequirement', 'StakeholderNeed', 'Project'] as $entity) {
            RoleEntityPermission::query()->create([
                'role_id' => $role->id, 'entity' => $entity,
                'can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => false,
                'can_approve' => $canApprove,
            ]);
        }

        return User::factory()->create([
            'name' => ucfirst($slug).' User', 'role_id' => $role->id,
            'tenant_id' => app(TenancyProvisioner::class)->ensureSharedTenant()->id,
            'workspace_id' => $this->project->workspace_id ?? null,
        ]);
    }

    protected function url(string $action): string
    {
        return route($action, ['model' => 'functional_requirement', 'id' => $this->fr->id]);
    }

    public function test_approve_sets_agreed_and_edit_resets_to_draft(): void
    {
        $this->postJson($this->url('review.approve'), ['note' => 'OK for release 1'])->assertOk();

        $this->fr->refresh();
        $this->assertSame(EntityStatus::id(EntityStatus::AGREED), (int) $this->fr->status_id);
        $this->assertSame(Approval::APPROVED, Approval::query()->active()->first()->decision);

        // Status/priority-only change keeps the approval.
        $this->fr->update(['priority_id' => $this->fr->priority_id]);
        $this->assertNotNull(Approval::query()->active()->first());

        // Content edit resets it.
        $this->fr->update(['statement' => 'The system shall create a ticket with a reference number.']);
        $this->assertNull(Approval::query()->active()->first());
        $this->assertSame(EntityStatus::id(EntityStatus::DRAFT), (int) $this->fr->fresh()->status_id);
        $this->assertTrue(ActivityLog::query()->where('event', 'approval_reset')->exists());
    }

    public function test_request_changes_needs_reason_posts_comment_and_sets_need_revision(): void
    {
        $this->postJson($this->url('review.changes'), ['note' => ''])->assertStatus(422);
        $this->postJson($this->url('review.changes'), ['note' => 'Add mandatory field list'])->assertOk();

        $this->assertSame(EntityStatus::id(EntityStatus::NEED_REVISION), (int) $this->fr->fresh()->status_id);
        $this->assertStringContainsString('Add mandatory field list', Comment::query()->firstOrFail()->body);
    }

    public function test_update_permission_alone_cannot_approve(): void
    {
        $this->actingAs($this->user('editor', false));
        $this->postJson($this->url('review.approve'))->assertForbidden();
    }

    public function test_history_records_field_changes_and_details_show_review_and_history(): void
    {
        $this->fr->update(['title' => 'Create support ticket']);
        $entry = ActivityLog::query()->where('event', 'updated')->firstOrFail();
        $this->assertSame(['Create ticket', 'Create support ticket'], $entry->changes['title']);

        $this->get(model_route('FunctionalRequirement', 'show', $this->fr->id))
            ->assertOk()
            ->assertSee('data-review-bar', false)
            ->assertSee(__('ui.review_approve'))
            ->assertSee(__('ui.history_title'))
            ->assertSee('Create support ticket');
    }

    public function test_home_lists_items_waiting_for_review_and_pdf_shows_signoff(): void
    {
        $this->get(route('home'))->assertOk()->assertSee(__('ui.home_reviews_title'))->assertSee('Create ticket');

        $this->postJson($this->url('review.approve'))->assertOk();
        $key = collect(config('babok_documents.documents'))
            ->filter(fn ($d) => collect($d['sections'])->contains('partial', 'solution-requirements'))->keys()->first();

        $this->get(route('projects.babok.show', [$this->project, $key]))
            ->assertOk()
            ->assertSee(__('ui.review_signoff_title'))
            ->assertSee('Approved by Owner User');
    }

    public function test_roles_matrix_saves_approve_only_for_approvable_entities(): void
    {
        $role = Role::query()->create(['name' => 'QA', 'slug' => 'qa']);
        RolePermissionSync::sync($role, [
            'FunctionalRequirement' => ['view' => 1, 'approve' => 1],
            'Risk' => ['view' => 1, 'approve' => 1],
        ]);

        $this->assertTrue((bool) $role->entityPermissions()->where('entity', 'FunctionalRequirement')->value('can_approve'));
        $this->assertFalse((bool) $role->entityPermissions()->where('entity', 'Risk')->value('can_approve'));
    }

    public function test_collaboration_guide_is_in_the_ba_guide(): void
    {
        $this->withHeader('X-Modal-Request', '1')->get(route('help.guide.show', 'collaboration'))
            ->assertOk()
            ->assertSee('Collaboration')
            ->assertSee('Request changes');
        $this->get(model_route('FunctionalRequirement', 'show', $this->fr->id))
            ->assertOk()
            ->assertSee(route('help.guide.show', 'collaboration'), false);
    }
}
