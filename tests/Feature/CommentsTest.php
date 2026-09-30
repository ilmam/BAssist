<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Models\StakeholderNeed;
use App\Models\User;
use App\Services\CommentService;
use App\Services\TenancyProvisioner;
use App\Support\EntityAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    use RefreshDatabase;

    protected Project $project;

    protected FunctionalRequirement $fr;

    protected User $admin;

    protected User $sara;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);
        $role = Role::query()->create(['name' => 'Super Admin', 'slug' => EntityAccess::SUPER_ADMIN_SLUG]);
        $this->admin = User::factory()->create(['name' => 'Ilmam Mourtada', 'role_id' => $role->id, 'tenant_id' => $tenant->id, 'workspace_id' => $workspace->id]);
        $this->sara = User::factory()->create(['name' => 'Sara Ali', 'role_id' => $role->id, 'tenant_id' => $tenant->id, 'workspace_id' => $workspace->id]);

        $this->project = Project::query()->create(['workspace_id' => $workspace->id, 'name' => 'Comments', 'code' => 'CMT']);
        $story = StakeholderNeed::query()->create(['project_id' => $this->project->id, 'title' => 'Dealer submits inquiry']);
        $this->fr = FunctionalRequirement::query()->create(['project_id' => $this->project->id, 'stakeholder_need_id' => $story->id, 'title' => 'Create ticket', 'statement' => 'The system shall create a ticket.']);
    }

    protected function storeUrl(): string
    {
        return route('comments.store', ['model' => 'functional_requirement', 'id' => $this->fr->id]);
    }

    public function test_post_reply_resolve_and_reopen(): void
    {
        $this->actingAs($this->admin);

        $this->post($this->storeUrl(), ['body' => 'Which fields are mandatory? @Sara please confirm'])
            ->assertOk()->assertSee('ba-mention', false)->assertSee('1 open comment');

        $thread = Comment::query()->firstOrFail();
        $this->assertSame([$this->sara->id], $thread->mentionedUsers()->pluck('users.id')->all());

        $this->post(route('comments.resolve', $thread), ['resolved' => 1])->assertOk()->assertSee(__('ui.comments_all_resolved'));
        $this->assertNotNull($thread->fresh()->resolved_at);

        // A reply re-opens the thread.
        $this->post($this->storeUrl(), ['body' => 'Still unclear for used parts', 'parent_id' => $thread->id])->assertOk();
        $this->assertNull($thread->fresh()->resolved_at);
        $this->assertSame(1, $thread->replies()->count());
    }

    public function test_details_page_shows_panel_and_mentions_reach_home(): void
    {
        $this->actingAs($this->admin);
        app(CommentService::class)->add($this->fr, 'Please review @Sara');

        $this->actingAs($this->sara)
            ->get(route('home'))
            ->assertOk()->assertSee(__('ui.home_mentions_title'))->assertSee('Create ticket');

        $this->get(model_route('FunctionalRequirement', 'show', $this->fr->id))
            ->assertOk()->assertSee('data-comments-panel', false);

        // Viewing the item marks the mention as seen.
        $this->get(route('home'))->assertOk()->assertDontSee(__('ui.home_mentions_title'));
    }

    public function test_only_author_can_delete_and_viewers_need_permission(): void
    {
        $this->actingAs($this->admin);
        $comment = app(CommentService::class)->add($this->fr, 'Mine');

        $noAccess = Role::query()->create(['name' => 'Guest', 'slug' => 'guest']);
        RoleEntityPermission::query()->create(['role_id' => $noAccess->id, 'entity' => 'Project', 'can_view' => true, 'can_create' => false, 'can_update' => false, 'can_delete' => false]);
        $guest = User::factory()->create(['role_id' => $noAccess->id, 'tenant_id' => $this->admin->tenant_id, 'workspace_id' => $this->admin->workspace_id]);

        $this->actingAs($guest)->post($this->storeUrl(), ['body' => 'Hi'])->assertForbidden();

        $analystRole = Role::query()->create(['name' => 'Analyst', 'slug' => 'analyst']);
        RoleEntityPermission::query()->create(['role_id' => $analystRole->id, 'entity' => 'FunctionalRequirement', 'can_view' => true, 'can_create' => false, 'can_update' => false, 'can_delete' => false]);
        $analyst = User::factory()->create(['role_id' => $analystRole->id, 'tenant_id' => $this->admin->tenant_id, 'workspace_id' => $this->admin->workspace_id]);
        $this->actingAs($analyst)->delete(route('comments.destroy', $comment))->assertForbidden();
        $this->actingAs($this->admin)->delete(route('comments.destroy', $comment))->assertOk();
        $this->assertSoftDeleted($comment);
    }

    public function test_pdf_prints_open_threads_inline_and_in_appendix_with_toggle(): void
    {
        $this->actingAs($this->admin);
        $service = app(CommentService::class);
        $service->add($this->fr, 'Mandatory fields list is missing');
        $resolved = $service->add($this->fr, 'Old question');
        $service->setResolved($resolved, true);

        $url = route('projects.babok.show', [$this->project, 'requirements-analysis-design-definition']);
        if (! array_key_exists('requirements-analysis-design-definition', config('babok_documents.documents'))) {
            $key = collect(config('babok_documents.documents'))->filter(fn ($d) => collect($d['sections'])->contains('partial', 'solution-requirements'))->keys()->first();
            $url = route('projects.babok.show', [$this->project, $key]);
        }

        $this->get($url)
            ->assertOk()
            ->assertSee('print-comments', false)
            ->assertSee('Mandatory fields list is missing')
            ->assertDontSee('Old question')
            ->assertSee(__('ui.comments_appendix_title'))
            ->assertSee('This document has 1 open comment');

        $this->get($url.'?comments=0')
            ->assertOk()
            ->assertDontSee('Mandatory fields list is missing')
            ->assertDontSee(__('ui.comments_appendix_title'));

        $this->get(route('projects.export', $this->project))
            ->assertOk()->assertSee('Mandatory fields list is missing');
    }

    public function test_every_babok_document_renders_with_comments_on(): void
    {
        $this->actingAs($this->admin);
        app(CommentService::class)->add($this->fr, 'Open question');

        foreach (array_keys(config('babok_documents.documents')) as $key) {
            $this->get(route('projects.babok.show', [$this->project, $key]))->assertOk();
        }
    }
}
