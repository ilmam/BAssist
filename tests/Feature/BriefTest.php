<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Brief;
use App\Models\Project;
use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workspace;
use App\Support\AttachableSupport;
use App\Support\BriefStatus;
use App\Support\NavTreeBuilder;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * docs/brief-entity.md: the ask as received, above projects. Projects may start
 * from a brief or without one; nothing depends on having one.
 */
class BriefTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Workspace $workspace;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->tenant = Tenant::query()->create(['name' => 'Acme', 'slug' => 'acme']);
        $this->workspace = Workspace::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Main', 'slug' => 'main']);

        $role = Role::query()->create(['name' => 'Analyst', 'slug' => 'analyst']);
        foreach (['Workspace', 'Project', 'Brief'] as $entity) {
            RoleEntityPermission::query()->create([
                'role_id' => $role->id, 'entity' => $entity,
                'can_view' => true, 'can_create' => true, 'can_update' => true, 'can_delete' => true,
            ]);
        }

        $this->user = User::factory()->create(['role_id' => $role->id, 'tenant_id' => $this->tenant->id, 'workspace_id' => $this->workspace->id]);
        $this->actingAs($this->user);
    }

    protected function brief(array $attributes = []): Brief
    {
        return Brief::query()->create(array_merge([
            'title' => 'Dealer app',
            'workspace_id' => $this->workspace->id,
            'ask' => 'Build us an app for dealers.',
        ], $attributes));
    }

    protected function project(string $name, string $code): Project
    {
        return Project::query()->create(['workspace_id' => $this->workspace->id, 'name' => $name, 'code' => $code]);
    }

    public function test_a_new_brief_is_received_and_attachable(): void
    {
        $brief = $this->brief();

        $this->assertSame(BriefStatus::RECEIVED, $brief->fresh()->status);
        $this->assertSame(1, (int) $brief->fresh()->version);
        $this->assertTrue(AttachableSupport::enabled('Brief'));
    }

    public function test_one_brief_can_have_no_project_one_project_or_many(): void
    {
        $dead = $this->brief(['title' => 'Parked idea']);
        $this->assertCount(0, $dead->projects);

        $brief = $this->brief();
        $platform = $this->project('Platform', 'PLT');
        $mobile = $this->project('Mobile', 'MOB');

        $brief->projects()->attach($platform->id, ['scope_note' => 'Back office and API']);
        $this->assertCount(1, $brief->fresh()->projects);

        $brief->projects()->attach($mobile->id, ['scope_note' => 'Dealer mobile app']);
        $brief = $brief->fresh();
        $this->assertCount(2, $brief->projects);
        $this->assertSame('Dealer mobile app', $brief->projects->firstWhere('id', $mobile->id)->pivot->scope_note);
    }

    public function test_a_project_can_start_without_a_brief_and_be_linked_later(): void
    {
        $project = $this->project('Internal tool', 'INT');
        $this->assertCount(0, $project->briefs);

        $brief = $this->brief();
        $project->briefs()->attach($brief->id, ['scope_note' => 'Found the original email later']);

        $this->assertCount(1, $project->fresh()->briefs);

        $project->briefs()->detach($brief->id);
        $this->assertCount(0, $project->fresh()->briefs);
    }

    public function test_a_project_can_come_from_two_briefs(): void
    {
        $project = $this->project('Merged request', 'MRG');
        $a = $this->brief(['title' => 'Request A']);
        $b = $this->brief(['title' => 'Request B']);

        $project->briefs()->attach([$a->id, $b->id]);

        $this->assertCount(2, $project->fresh()->briefs);
    }

    public function test_declined_or_on_hold_needs_a_reason(): void
    {
        $brief = $this->brief();

        try {
            $brief->update(['status' => BriefStatus::DECLINED]);
            $this->fail('A declined brief without a reason must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status_reason', $e->errors());
        }

        $this->expectException(ValidationException::class);
        $this->brief(['status' => BriefStatus::ON_HOLD]);
    }

    public function test_declined_with_a_reason_is_a_valid_dead_end(): void
    {
        $brief = $this->brief();
        $brief->update(['status' => BriefStatus::DECLINED, 'status_reason' => 'Out of budget this year']);

        $this->assertSame(BriefStatus::DECLINED, $brief->fresh()->status);
        $this->assertCount(0, $brief->fresh()->projects);
    }

    public function test_approving_needs_the_ask_or_an_attachment(): void
    {
        $brief = $this->brief(['ask' => null]);

        try {
            $brief->update(['status' => BriefStatus::APPROVED]);
            $this->fail('Approving an empty brief must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('ask', $e->errors());
        }

        Storage::fake('local');
        Attachment::query()->create([
            'attachable_type' => Brief::class,
            'attachable_id' => $brief->id,
            'original_name' => 'original-email.pdf',
            'disk' => 'local',
            'path' => 'attachments/original-email.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);

        $brief->fresh()->update(['status' => BriefStatus::APPROVED]);
        $this->assertTrue($brief->fresh()->isApproved());
    }

    public function test_a_rebrief_is_a_new_version_and_keeps_the_original(): void
    {
        $original = $this->brief(['ask' => 'Build us an app for dealers.']);

        $next = $original->newVersion(['ask' => 'Build us an app for dealers and workshops.']);

        $this->assertSame(2, (int) $next->version);
        $this->assertSame($original->id, $next->previous_version_id);
        $this->assertSame(BriefStatus::RECEIVED, $next->status);
        $this->assertSame('Build us an app for dealers.', $original->fresh()->ask);
        $this->assertSame(1, (int) $original->fresh()->version);
    }

    public function test_briefs_of_another_tenant_are_invisible(): void
    {
        $this->brief();

        $foreign = Tenancy::bypass(function () {
            $tenant = Tenant::query()->create(['name' => 'Other', 'slug' => 'other']);
            $workspace = Workspace::query()->create(['tenant_id' => $tenant->id, 'name' => 'Else', 'slug' => 'else']);

            return Brief::query()->withoutGlobalScopes()->create(['title' => 'Secret', 'workspace_id' => $workspace->id, 'ask' => 'x']);
        });

        $this->assertSame(['Dealer app'], Brief::query()->pluck('title')->all());
        $this->assertNull(Brief::query()->find($foreign->id));
    }

    public function test_the_menu_lists_briefs_under_each_workspace(): void
    {
        $this->project('Menu project', 'MNU');
        $this->brief();

        $labels = [];
        $walk = function (array $items) use (&$walk, &$labels): void {
            foreach ($items as $item) {
                $labels[] = $item['label'] ?? '';
                $walk($item['children'] ?? []);
            }
        };
        $walk(app(NavTreeBuilder::class)->build());

        $this->assertContains('Briefs', $labels);
        $this->assertContains('Projects', $labels);
    }

    public function test_brief_pages_open_and_save(): void
    {
        $this->get(route('briefs.index'))->assertOk();

        $this->get(route('briefs.create'))->assertOk()->assertSee('name="attachments[]"', false)
            ->assertSee('type="date"', false);

        $this->post(route('briefs.store'), [
            'title' => 'From the form',
            'workspace_id' => $this->workspace->id,
            'ask' => 'Please build X.',
            'status' => BriefStatus::RECEIVED,
        ])->assertRedirect();

        $brief = Brief::query()->where('title', 'From the form')->firstOrFail();
        $this->get(route('briefs.show', $brief->id))->assertOk()->assertSee('Please build X.');
    }

    public function test_linking_from_the_brief_page_sets_updates_and_removes_the_scope_note(): void
    {
        $brief = $this->brief();
        $project = $this->project('Platform', 'PLT');

        $this->get(route('briefs.show', $brief->id))->assertOk()->assertSee('Platform');

        $this->post(route('briefs.projects.store', $brief->id), ['project_id' => $project->id, 'scope_note' => 'API'])
            ->assertRedirect();
        $this->assertSame('API', $brief->fresh()->projects->first()->pivot->scope_note);

        $this->get(route('briefs.show', $brief->id))->assertOk()->assertSee('API');

        $this->patch(route('briefs.projects.update', [$brief->id, $project->id]), ['scope_note' => 'API and back office'])
            ->assertRedirect();
        $this->assertSame('API and back office', $brief->fresh()->projects->first()->pivot->scope_note);

        // Linking twice never duplicates.
        $this->post(route('briefs.projects.store', $brief->id), ['project_id' => $project->id, 'scope_note' => 'Again']);
        $this->assertCount(1, $brief->fresh()->projects);

        $this->delete(route('briefs.projects.destroy', [$brief->id, $project->id]))->assertRedirect();
        $this->assertCount(0, $brief->fresh()->projects);
    }

    public function test_a_project_started_without_a_brief_can_be_linked_from_its_own_page(): void
    {
        $project = $this->project('Internal tool', 'INT');
        $brief = $this->brief();

        $this->get(route('projects.dashboard', $project))->assertOk()->assertSee('Link a brief');

        $this->post(route('projects.briefs.store', $project), ['brief_id' => $brief->id, 'scope_note' => 'Original email found'])
            ->assertRedirect();

        $this->assertCount(1, $project->fresh()->briefs);
        $this->get(route('projects.dashboard', $project))->assertOk()->assertSee('Original email found');
    }

    public function test_a_project_without_any_brief_is_not_flagged(): void
    {
        $project = $this->project('Quick fix', 'QCK');

        $this->get(route('projects.dashboard', $project))
            ->assertOk()
            ->assertDontSee('From brief');
    }

    public function test_a_brief_cannot_be_linked_to_a_project_of_another_workspace(): void
    {
        $other = Workspace::query()->create(['tenant_id' => $this->tenant->id, 'name' => 'Second', 'slug' => 'second']);
        $project = Project::query()->create(['workspace_id' => $other->id, 'name' => 'Elsewhere', 'code' => 'ELS']);
        $brief = $this->brief();

        $this->post(route('briefs.projects.store', $brief->id), ['project_id' => $project->id])
            ->assertSessionHasErrors('project_id');
        $this->assertCount(0, $brief->fresh()->projects);
    }

    public function test_a_declined_brief_does_not_start_a_new_project(): void
    {
        $brief = $this->brief();
        $brief->update(['status' => BriefStatus::DECLINED, 'status_reason' => 'Out of scope']);

        $this->post(route('briefs.create-project', $brief->id), ['name' => 'Dealer app'])
            ->assertSessionHasErrors('name');
        $this->assertSame(0, Project::query()->where('name', 'Dealer app')->count());
    }

    public function test_a_brief_starts_a_new_project_and_the_brief_page_links_to_it(): void
    {
        $brief = $this->brief();

        $response = $this->post(route('briefs.create-project', $brief->id), [
            'name' => 'Dealer app',
            'code' => 'DLR',
            'scope_note' => 'Everything in the first release',
        ]);

        $project = Project::query()->where('name', 'Dealer app')->firstOrFail();
        $response->assertRedirect(route('projects.dashboard', $project));
        $this->assertSame($this->workspace->id, $project->workspace_id);
        $this->assertSame('Everything in the first release', $project->briefs->first()->pivot->scope_note);

        $this->get(route('briefs.show', $brief->id))
            ->assertOk()
            ->assertSee('Dealer app')
            ->assertSee(route('projects.dashboard', $project), false);
    }

    public function test_the_export_pack_opens_with_the_brief_when_there_is_one(): void
    {
        $with = $this->project('With brief', 'WTH');
        $without = $this->project('Without brief', 'WTO');
        $brief = $this->brief();
        $brief->update(['stated_constraints' => 'Live by March']);
        $with->briefs()->attach($brief->id, ['scope_note' => 'The dealer side only']);

        $html = $this->get(route('projects.export', $with))->assertOk()
            ->assertSee('section-briefs', false)
            ->assertSee('Live by March')
            ->assertSee('The dealer side only')
            ->getContent();

        $briefAt = strpos($html, '<h2 id="section-briefs"');
        $readinessAt = strpos($html, '>'.e(__('ui.project_readiness')).'</h2>');
        $this->assertNotFalse($briefAt);
        $this->assertNotFalse($readinessAt);
        $this->assertLessThan($readinessAt, $briefAt);

        $this->get(route('projects.export', $without))->assertOk()->assertDontSee('<h2 id="section-briefs"', false);
    }

    public function test_the_project_list_can_filter_by_having_a_brief(): void
    {
        $with = $this->project('With brief', 'WTH');
        $this->project('Without brief', 'WTO');
        $with->briefs()->attach($this->brief()->id);

        $repository = new \App\Repositories\ProjectRepository();

        $names = fn (string $value) => collect($repository->getAll(['has_brief' => $value]))->pluck('name')->all();

        $this->assertSame(['With brief'], $names('yes'));
        $this->assertSame(['Without brief'], $names('no'));
        $this->assertEqualsCanonicalizing(['With brief', 'Without brief'], $names(''));
    }

    public function test_attachments_save_with_a_brief_form(): void
    {
        Storage::fake('local');

        $this->post(route('briefs.store'), [
            'title' => 'Only an email',
            'workspace_id' => $this->workspace->id,
            'status' => BriefStatus::RECEIVED,
            'attachments' => [UploadedFile::fake()->create('original.pdf', 60, 'application/pdf')],
        ])->assertRedirect();

        $brief = Brief::query()->where('title', 'Only an email')->firstOrFail();
        $this->assertSame(1, $brief->attachments()->count());
    }
}
