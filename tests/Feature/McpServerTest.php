<?php

namespace Tests\Feature;

use App\Mcp\Servers\BAssistServer;
use App\Mcp\Tools\AddCommentTool;
use App\Mcp\Tools\CreateRecordTool;
use App\Mcp\Tools\DeleteRecordTool;
use App\Mcp\Tools\DescribeEntityTool;
use App\Mcp\Tools\GetGherkinTool;
use App\Mcp\Tools\GetLineageTool;
use App\Mcp\Tools\GetReadinessTool;
use App\Mcp\Tools\GetRecordTool;
use App\Mcp\Tools\ListCommentsTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ListRecordsTool;
use App\Mcp\Tools\UpdateRecordTool;
use App\Models\ActivityLog;
use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\Comment;
use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Models\Scenario;
use App\Models\StakeholderNeed;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workspace;
use App\Services\CommentService;
use App\Services\ProjectReadinessService;
use App\Support\ApiTokenAbility;
use App\Support\EntityAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The MCP endpoint (docs/mcp.md). An assistant acts as the token's owner, so
 * tenant isolation, role permissions and token abilities must all hold, and
 * what it changes must be labelled in the record history.
 */
class McpServerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array{tenant: Tenant, workspace: Workspace, project: Project, need: StakeholderNeed, fr: FunctionalRequirement, feature: Feature} */
    protected array $a;

    /** @var array{tenant: Tenant, workspace: Workspace, project: Project, need: StakeholderNeed, fr: FunctionalRequirement, feature: Feature} */
    protected array $b;

    protected User $userA;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\Laravel\Mcp\Server::class)) {
            $this->markTestSkipped('laravel/mcp is not installed. Run: composer require laravel/mcp');
        }

        $this->a = $this->seedTenant('Alpha');
        $this->b = $this->seedTenant('Bravo');

        // A super-admin who belongs to a tenant is confined to it (see Tenancy).
        $role = Role::query()->create(['name' => 'Super Admin', 'slug' => EntityAccess::SUPER_ADMIN_SLUG]);
        $this->userA = User::factory()->create([
            'role_id' => $role->id,
            'tenant_id' => $this->a['tenant']->id,
            'workspace_id' => $this->a['workspace']->id,
        ]);
    }

    // --- Tools called directly, as a user -----------------------------------

    public function test_list_projects_shows_only_the_users_tenant(): void
    {
        BAssistServer::actingAs($this->userA)
            ->tool(ListProjectsTool::class)
            ->assertOk()
            ->assertSee('Alpha project')
            ->assertDontSee('Bravo project');
    }

    public function test_readiness_for_own_project_and_not_found_for_a_foreign_one(): void
    {
        BAssistServer::actingAs($this->userA)
            ->tool(GetReadinessTool::class, ['project_id' => $this->a['project']->id])
            ->assertOk()
            ->assertSee(['total_gaps', 'spine']);

        BAssistServer::actingAs($this->userA)
            ->tool(GetReadinessTool::class, ['project_id' => $this->b['project']->id])
            ->assertHasErrors();
    }

    public function test_lineage_and_gherkin(): void
    {
        BAssistServer::actingAs($this->userA)
            ->tool(GetLineageTool::class, ['entity' => 'Feature', 'id' => $this->a['feature']->id])
            ->assertOk()
            ->assertSee('lineage');

        BAssistServer::actingAs($this->userA)
            ->tool(GetGherkinTool::class, ['feature_id' => $this->a['feature']->id])
            ->assertOk()
            ->assertSee('Alpha scenario');

        BAssistServer::actingAs($this->userA)
            ->tool(GetGherkinTool::class, ['feature_id' => $this->b['feature']->id])
            ->assertHasErrors();

        BAssistServer::actingAs($this->userA)
            ->tool(GetGherkinTool::class, [])
            ->assertHasErrors();
    }

    public function test_describe_entity_lists_entities_and_fields(): void
    {
        BAssistServer::actingAs($this->userA)
            ->tool(DescribeEntityTool::class)
            ->assertOk()
            ->assertSee('StakeholderNeed')
            ->assertDontSee('"Tenant"');

        BAssistServer::actingAs($this->userA)
            ->tool(DescribeEntityTool::class, ['entity' => 'functional_requirements'])
            ->assertOk()
            ->assertSee(['acceptance_criteria', 'stakeholder_need_id', 'required']);

        BAssistServer::actingAs($this->userA)
            ->tool(DescribeEntityTool::class, ['entity' => 'Tenant'])
            ->assertHasErrors();
    }

    public function test_list_and_get_records_stay_inside_the_tenant(): void
    {
        BAssistServer::actingAs($this->userA)
            ->tool(ListRecordsTool::class, ['entity' => 'StakeholderNeed'])
            ->assertOk()
            ->assertSee('Alpha need')
            ->assertDontSee('Bravo need');

        BAssistServer::actingAs($this->userA)
            ->tool(ListRecordsTool::class, ['entity' => 'StakeholderNeed', 'search' => 'no such thing'])
            ->assertOk()
            ->assertDontSee('Alpha need');

        BAssistServer::actingAs($this->userA)
            ->tool(GetRecordTool::class, ['entity' => 'FunctionalRequirement', 'id' => $this->a['fr']->id])
            ->assertOk()
            ->assertSee('Alpha requirement');

        BAssistServer::actingAs($this->userA)
            ->tool(GetRecordTool::class, ['entity' => 'FunctionalRequirement', 'id' => $this->b['fr']->id])
            ->assertHasErrors();
    }

    public function test_create_record_validates_and_links_lineage(): void
    {
        BAssistServer::actingAs($this->userA)
            ->tool(CreateRecordTool::class, [
                'entity' => 'FunctionalRequirement',
                'data' => [
                    'project_id' => $this->a['project']->id,
                    'stakeholder_need_id' => $this->a['need']->id,
                    'title' => 'Export inquiries',
                    'statement' => 'The system shall export inquiries.',
                ],
            ])
            ->assertOk()
            ->assertSee('Export inquiries');

        $created = FunctionalRequirement::query()->where('title', 'Export inquiries')->sole();
        $this->assertSame($this->a['need']->id, (int) $created->stakeholder_need_id);

        // Missing required fields: nothing is saved.
        BAssistServer::actingAs($this->userA)
            ->tool(CreateRecordTool::class, [
                'entity' => 'FunctionalRequirement',
                'data' => ['project_id' => $this->a['project']->id],
            ])
            ->assertHasErrors();
        $this->assertSame(2, FunctionalRequirement::query()->count());
    }

    public function test_create_record_cannot_reference_another_tenant(): void
    {
        BAssistServer::actingAs($this->userA)
            ->tool(CreateRecordTool::class, [
                'entity' => 'FunctionalRequirement',
                'data' => [
                    'project_id' => $this->b['project']->id,
                    'stakeholder_need_id' => $this->b['need']->id,
                    'title' => 'Planted',
                    'statement' => 'Planted.',
                ],
            ])
            ->assertHasErrors();

        $this->assertSame(0, FunctionalRequirement::withoutGlobalScopes()->where('title', 'Planted')->count());
    }

    public function test_update_record_changes_only_the_fields_sent(): void
    {
        $fr = $this->a['fr'];

        BAssistServer::actingAs($this->userA)
            ->tool(UpdateRecordTool::class, [
                'entity' => 'FunctionalRequirement',
                'id' => $fr->id,
                'data' => ['acceptance_criteria' => 'Given a dealer, the answer arrives within a day.'],
            ])
            ->assertOk();

        $fr->refresh();
        $this->assertSame('Given a dealer, the answer arrives within a day.', $fr->acceptance_criteria);
        $this->assertSame('Alpha requirement', $fr->title);
        $this->assertSame('The system shall serve Alpha.', $fr->statement);
        $this->assertSame($this->a['need']->id, (int) $fr->stakeholder_need_id);

        BAssistServer::actingAs($this->userA)
            ->tool(UpdateRecordTool::class, [
                'entity' => 'FunctionalRequirement',
                'id' => $this->b['fr']->id,
                'data' => ['title' => 'Hijacked'],
            ])
            ->assertHasErrors();
        $this->assertSame('Bravo requirement', FunctionalRequirement::withoutGlobalScopes()->find($this->b['fr']->id)->title);
    }

    public function test_update_keeps_an_objectives_link_to_its_business_need(): void
    {
        $objective = BusinessObjective::query()->where('project_id', $this->a['project']->id)->sole();
        $needId = $objective->businessNeeds()->sole()->id;

        BAssistServer::actingAs($this->userA)
            ->tool(UpdateRecordTool::class, [
                'entity' => 'BusinessObjective',
                'id' => $objective->id,
                'data' => ['success_measure' => 'Answers within one working day.'],
            ])
            ->assertOk();

        $objective->refresh();
        $this->assertSame('Answers within one working day.', $objective->success_measure);
        $this->assertSame([$needId], $objective->businessNeeds()->pluck('business_needs.id')->all());
    }

    public function test_delete_record(): void
    {
        BAssistServer::actingAs($this->userA)
            ->tool(DeleteRecordTool::class, ['entity' => 'FunctionalRequirement', 'id' => $this->a['fr']->id])
            ->assertOk();

        $this->assertNull(FunctionalRequirement::query()->find($this->a['fr']->id));
    }

    public function test_role_permissions_apply_to_tools(): void
    {
        $role = Role::query()->create(['name' => 'Viewer', 'slug' => 'viewer']);
        RoleEntityPermission::query()->create([
            'role_id' => $role->id,
            'entity' => 'FunctionalRequirement',
            'can_view' => true,
            'can_create' => false,
            'can_update' => false,
            'can_delete' => false,
        ]);
        $viewer = User::factory()->create([
            'role_id' => $role->id,
            'tenant_id' => $this->a['tenant']->id,
            'workspace_id' => $this->a['workspace']->id,
        ]);

        BAssistServer::actingAs($viewer)
            ->tool(GetRecordTool::class, ['entity' => 'FunctionalRequirement', 'id' => $this->a['fr']->id])
            ->assertOk();

        BAssistServer::actingAs($viewer)
            ->tool(UpdateRecordTool::class, [
                'entity' => 'FunctionalRequirement',
                'id' => $this->a['fr']->id,
                'data' => ['title' => 'Changed'],
            ])
            ->assertHasErrors();

        BAssistServer::actingAs($viewer)
            ->tool(GetReadinessTool::class, ['project_id' => $this->a['project']->id])
            ->assertHasErrors();

        $this->assertSame('Alpha requirement', $this->a['fr']->fresh()->title);
    }

    // --- Findings as comments ---------------------------------------------------

    public function test_a_finding_is_a_comment_on_the_record_it_concerns(): void
    {
        $fr = $this->a['fr'];

        BAssistServer::actingAs($this->userA)
            ->tool(AddCommentTool::class, [
                'entity' => 'FunctionalRequirement',
                'id' => $fr->id,
                'body' => 'Finding: no minimum bid increment is specified. Observed: any amount is accepted. Suggested: a setting. Evidence: verified.',
            ])
            ->assertOk()
            ->assertSee(['no minimum bid increment', 'Alpha requirement']);

        $thread = Comment::withoutGlobalScopes()->sole();
        $this->assertSame(FunctionalRequirement::class, $thread->commentable_type);
        $this->assertSame($this->a['project']->id, (int) $thread->project_id);
        $this->assertSame($this->userA->id, (int) $thread->user_id);

        // It shows on the project, on the record's lineage, and in readiness.
        BAssistServer::actingAs($this->userA)
            ->tool(ListCommentsTool::class, ['project_id' => $this->a['project']->id])
            ->assertOk()
            ->assertSee('no minimum bid increment');

        BAssistServer::actingAs($this->userA)
            ->tool(GetLineageTool::class, ['entity' => 'FunctionalRequirement', 'id' => $fr->id])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('open_comments', 1)->etc());

        $this->actingAs($this->userA);
        $item = collect(app(ProjectReadinessService::class)->forProject($this->a['project'])['items'])
            ->firstWhere('key', 'open_comment_threads');
        $this->assertSame(1, $item['count']);
    }

    public function test_resolving_is_done_by_a_person_and_then_the_finding_drops_out(): void
    {
        $fr = $this->a['fr'];
        $this->actingAs($this->userA);
        $comments = app(CommentService::class);
        $thread = $comments->add($fr, 'Finding: which time zone?');

        $since = now()->subMinute()->toIso8601String();
        $comments->setResolved($thread, true);

        BAssistServer::actingAs($this->userA)
            ->tool(ListCommentsTool::class, ['project_id' => $this->a['project']->id])
            ->assertOk()
            ->assertDontSee('which time zone');

        BAssistServer::actingAs($this->userA)
            ->tool(ListCommentsTool::class, ['project_id' => $this->a['project']->id, 'state' => 'resolved', 'since' => $since])
            ->assertOk()
            ->assertSee(['which time zone', 'resolved']);

        BAssistServer::actingAs($this->userA)
            ->tool(GetLineageTool::class, ['entity' => 'FunctionalRequirement', 'id' => $fr->id])
            ->assertStructuredContent(fn ($json) => $json->where('open_comments', 0)->etc());

        // A reply re-opens the thread.
        BAssistServer::actingAs($this->userA)
            ->tool(AddCommentTool::class, [
                'entity' => 'FunctionalRequirement',
                'id' => $fr->id,
                'body' => 'Dealers outside Iraq too?',
                'reply_to' => $thread->id,
            ])
            ->assertOk();
        $this->assertNull($thread->fresh()->resolved_at);
    }

    public function test_posting_the_same_finding_twice_does_not_duplicate_it(): void
    {
        $arguments = ['entity' => 'FunctionalRequirement', 'id' => $this->a['fr']->id, 'body' => 'Finding: G-04 bid limits are not specified.'];

        BAssistServer::actingAs($this->userA)->tool(AddCommentTool::class, $arguments)->assertOk();
        BAssistServer::actingAs($this->userA)
            ->tool(AddCommentTool::class, $arguments)
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json->where('already_posted', true)->etc());

        // Still one, even after a person has resolved it.
        $this->actingAs($this->userA);
        app(CommentService::class)->setResolved(Comment::query()->sole(), true);
        BAssistServer::actingAs($this->userA)->tool(AddCommentTool::class, $arguments)->assertOk();

        $this->assertSame(1, Comment::withoutGlobalScopes()->count());
        $this->assertNotNull(Comment::withoutGlobalScopes()->sole()->resolved_at);
    }

    public function test_every_open_comment_is_listed_on_one_page_and_printed_with_its_item(): void
    {
        $this->withoutVite();
        $this->actingAs($this->userA);
        $comments = app(CommentService::class);
        $comments->add($this->a['feature'], 'Feature note: bid limits unclear');
        $comments->add($this->a['fr'], 'Requirement note: columns unclear');
        $comments->add($this->a['project'], 'Project note: time zone');
        // A record the export pack does not print with a comment margin.
        $stakeholder = \App\Models\Stakeholder::query()->where('project_id', $this->a['project']->id)->first()
            ?? \App\Models\Stakeholder::query()->create(['project_id' => $this->a['project']->id, 'name' => 'Dealer']);
        $comments->add($stakeholder, 'Stakeholder note: who signs off');
        $resolved = $comments->add($this->a['fr'], 'Already answered');
        $comments->setResolved($resolved, true);

        // Readiness links to the page, and the page lists every open thread with its record.
        $item = collect(app(ProjectReadinessService::class)->forProject($this->a['project'])['items'])
            ->firstWhere('key', 'open_comment_threads');
        $this->assertSame(4, $item['count']);
        $this->assertSame(route('projects.comments', $this->a['project']), $item['url']);

        $this->get($item['url'])
            ->assertOk()
            ->assertSee(['Feature note: bid limits unclear', 'Requirement note: columns unclear', 'Project note: time zone'])
            ->assertSee(['Alpha feature', 'Alpha requirement'])
            ->assertDontSee('Already answered');

        // Another tenant's project is not found.
        $this->get(route('projects.comments', $this->b['project']->id))->assertNotFound();

        // The export pack prints a feature's comments next to the feature.
        $this->get(route('projects.export', $this->a['project']))
            ->assertOk()
            ->assertSee(['Feature note: bid limits unclear', 'Requirement note: columns unclear'])
            // …and lists a comment on a record it does not print, so none is left out.
            ->assertSee('Stakeholder note: who signs off')
            ->assertDontSee('Already answered');

        $this->get(route('projects.export', ['project' => $this->a['project'], 'comments' => 0]))
            ->assertOk()
            ->assertDontSee(['Feature note: bid limits unclear', 'Stakeholder note: who signs off']);
    }

    public function test_project_wide_findings_go_on_the_project(): void
    {
        BAssistServer::actingAs($this->userA)
            ->tool(AddCommentTool::class, [
                'entity' => 'Project',
                'id' => $this->a['project']->id,
                'body' => 'Finding: retention period for bids is not stated anywhere.',
            ])
            ->assertOk();

        $thread = Comment::withoutGlobalScopes()->sole();
        $this->assertSame(Project::class, $thread->commentable_type);
        $this->assertSame($this->a['project']->id, (int) $thread->project_id);

        BAssistServer::actingAs($this->userA)
            ->tool(ListCommentsTool::class, ['entity' => 'Project', 'id' => $this->a['project']->id])
            ->assertOk()
            ->assertSee('retention period');

        // They show on the project dashboard, the page people actually open.
        $this->withoutVite();
        $this->actingAs($this->userA)
            ->get(route('projects.dashboard', $this->a['project']))
            ->assertOk()
            ->assertSee('retention period');

        // And in the export pack, as a margin note beside the title and nowhere
        // else in the document flow, unless comments are switched off for the final copy.
        $export = $this->get(route('projects.export', $this->a['project']))->assertOk()->assertSee('retention period');
        $beforeAppendix = \Illuminate\Support\Str::before($export->getContent(), 'class="print-appendix"');
        $this->assertSame(1, substr_count($beforeAppendix, 'retention period'));
        $this->assertStringNotContainsString('section-project-comments', $export->getContent());
        $this->get(route('projects.export', ['project' => $this->a['project'], 'comments' => 0]))
            ->assertOk()
            ->assertDontSee('retention period');
    }

    public function test_comments_respect_tenant_and_supported_entities(): void
    {
        BAssistServer::actingAs($this->userA)
            ->tool(AddCommentTool::class, ['entity' => 'FunctionalRequirement', 'id' => $this->b['fr']->id, 'body' => 'Planted'])
            ->assertHasErrors();

        BAssistServer::actingAs($this->userA)
            ->tool(ListCommentsTool::class, ['project_id' => $this->b['project']->id])
            ->assertHasErrors();

        // Scenarios do not take comments; the finding belongs on their feature.
        BAssistServer::actingAs($this->userA)
            ->tool(AddCommentTool::class, ['entity' => 'Scenario', 'id' => 1, 'body' => 'Unclear step'])
            ->assertHasErrors();

        $this->assertSame(0, Comment::withoutGlobalScopes()->count());
    }

    public function test_comment_through_the_endpoint_needs_write_and_is_labelled(): void
    {
        $arguments = ['entity' => 'FunctionalRequirement', 'id' => $this->a['fr']->id, 'body' => 'Finding: unclear.'];

        $this->usingToken($this->userA, [ApiTokenAbility::READ])
            ->callTool('add-comment', $arguments)
            ->assertOk()
            ->assertJsonPath('result.isError', true);
        $this->assertSame(0, Comment::withoutGlobalScopes()->count());

        $this->usingToken($this->userA, [ApiTokenAbility::READ, ApiTokenAbility::WRITE])
            ->callTool('add-comment', $arguments)
            ->assertOk()
            ->assertJsonPath('result.isError', false)
            ->assertJsonPath('result.structuredContent.via', 'mcp');
        $this->assertSame('mcp', Comment::withoutGlobalScopes()->sole()->via);
    }

    // --- Over HTTP, with real tokens ------------------------------------------

    public function test_endpoint_requires_a_token(): void
    {
        $this->rpc('tools/list')->assertUnauthorized();
    }

    public function test_endpoint_lists_the_tools(): void
    {
        $names = array_column(
            $this->usingToken($this->userA, [ApiTokenAbility::READ])->rpc('tools/list')->assertOk()->json('result.tools'),
            'name',
        );

        foreach (['list-projects', 'get-readiness', 'get-lineage', 'describe-entity', 'create-record', 'update-record', 'delete-record', 'list-comments', 'add-comment'] as $tool) {
            $this->assertContains($tool, $names);
        }
    }

    public function test_read_only_token_can_read_but_not_write(): void
    {
        $client = $this->usingToken($this->userA, [ApiTokenAbility::READ]);

        $client->callTool('get-readiness', ['project_id' => $this->a['project']->id])
            ->assertOk()
            ->assertJsonPath('result.isError', false);

        $response = $client->callTool('update-record', [
            'entity' => 'FunctionalRequirement',
            'id' => $this->a['fr']->id,
            'data' => ['title' => 'Changed'],
        ])->assertOk()->assertJsonPath('result.isError', true);

        $this->assertStringContainsString('read only', $response->json('result.content.0.text'));
        $this->assertSame('Alpha requirement', $this->a['fr']->fresh()->title);
    }

    public function test_changes_are_labelled_by_channel_in_the_history(): void
    {
        $fr = $this->a['fr'];
        $history = fn () => ActivityLog::withoutGlobalScopes()
            ->where('subject_type', FunctionalRequirement::class)
            ->where('subject_id', $fr->id)
            ->where('event', 'updated')
            ->orderBy('id')
            ->get();

        // Through the MCP endpoint.
        $this->usingToken($this->userA, [ApiTokenAbility::READ, ApiTokenAbility::WRITE])
            ->callTool('update-record', [
                'entity' => 'FunctionalRequirement',
                'id' => $fr->id,
                'data' => ['title' => 'Renamed by assistant'],
            ])
            ->assertOk()
            ->assertJsonPath('result.isError', false);

        $entry = $history()->last();
        $this->assertSame('mcp', $entry->via);
        $this->assertSame($this->userA->id, (int) $entry->user_id);
        $this->assertSame('Renamed by assistant', $fr->fresh()->title);
    }

    public function test_api_token_and_browser_changes_are_labelled_too(): void
    {
        $fr = $this->a['fr'];
        $payload = fn (string $title) => [
            'title' => $title,
            'project_id' => $this->a['project']->id,
            'stakeholder_need_id' => $this->a['need']->id,
            'statement' => 'The system shall serve Alpha.',
        ];
        $last = fn () => ActivityLog::withoutGlobalScopes()
            ->where('subject_type', FunctionalRequirement::class)
            ->where('subject_id', $fr->id)
            ->where('event', 'updated')
            ->orderByDesc('id')
            ->first();

        $this->usingToken($this->userA, [ApiTokenAbility::READ, ApiTokenAbility::WRITE])
            ->putJson(route('api.functional_requirement.update', $fr->id), $payload('Renamed by script'))
            ->assertOk();
        $this->assertSame('api', $last()->via);

        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->userA)
            ->putJson(route('api.functional_requirement.update', $fr->id), $payload('Renamed in the browser'))
            ->assertOk();
        $this->assertNull($last()->via);
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    protected function callTool(string $name, array $arguments = []): TestResponse
    {
        return $this->rpc('tools/call', ['name' => $name, 'arguments' => $arguments]);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function rpc(string $method, array $params = []): TestResponse
    {
        return $this->postJson('/mcp', array_filter([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params === [] ? null : $params,
        ], fn ($value) => $value !== null), ['Accept' => 'application/json, text/event-stream']);
    }

    /**
     * @param  list<string>  $abilities
     */
    protected function usingToken(User $user, array $abilities): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('test', $abilities)->plainTextToken);
    }

    /**
     * @return array{tenant: Tenant, workspace: Workspace, project: Project, need: StakeholderNeed, fr: FunctionalRequirement, feature: Feature}
     */
    protected function seedTenant(string $name): array
    {
        $tenant = Tenant::query()->create(['name' => $name, 'slug' => strtolower($name)]);
        $workspace = Workspace::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $name.' workspace',
            'slug' => strtolower($name).'-ws',
        ]);
        $project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => $name.' project',
            'code' => strtoupper(substr($name, 0, 3)),
        ]);

        $businessNeed = BusinessNeed::query()->create(['project_id' => $project->id, 'title' => $name.' business need']);
        $objective = BusinessObjective::query()->create(['project_id' => $project->id, 'title' => $name.' objective']);
        $objective->businessNeeds()->sync([$businessNeed->id => ['is_primary' => true]]);

        $need = StakeholderNeed::query()->create(['project_id' => $project->id, 'title' => $name.' need']);
        $need->businessObjectives()->attach($objective->id);

        $fr = FunctionalRequirement::query()->create([
            'project_id' => $project->id,
            'stakeholder_need_id' => $need->id,
            'title' => $name.' requirement',
            'statement' => 'The system shall serve '.$name.'.',
        ]);
        $feature = Feature::query()->create([
            'project_id' => $project->id,
            'stakeholder_need_id' => $need->id,
            'title' => $name.' feature',
            'body' => 'Feature: '.$name." feature\n",
        ]);
        Scenario::query()->create([
            'feature_id' => $feature->id,
            'title' => $name.' scenario',
            'body' => 'Scenario: '.$name." scenario\n  Given a dealer\n  When they ask\n  Then they are answered\n",
        ]);

        return compact('tenant', 'workspace', 'project', 'need', 'fr', 'feature');
    }
}
