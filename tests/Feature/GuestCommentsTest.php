<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use App\Services\CommentService;
use App\Support\ProjectAccessLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsProjectAccess;
use Tests\TestCase;

class GuestCommentsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProjectAccess;

    /** @var array<string, mixed> */
    protected array $a;

    protected function setUp(): void
    {
        parent::setUp();

        $this->a = $this->seedTenant('Alpha');
    }

    protected function guestWith(ProjectAccessLevel $level, string $name = 'Guest One'): User
    {
        $guest = User::factory()->guest()->create(['name' => $name]);
        $this->grant($guest, $this->a['project'], $level);

        return $guest;
    }

    protected function commentsUrl(): string
    {
        return route('comments.store', ['model' => 'functional_requirement', 'id' => $this->a['fr']->id]);
    }

    public function test_view_guest_cannot_comment(): void
    {
        $this->actingAs($this->guestWith(ProjectAccessLevel::View));

        $this->post($this->commentsUrl(), ['body' => 'Hello'])->assertForbidden();

        $this->assertSame(0, Comment::query()->count());
    }

    public function test_comment_guest_can_comment_and_reply_but_not_close_a_thread(): void
    {
        $this->actingAs($this->guestWith(ProjectAccessLevel::Comment));

        $this->post($this->commentsUrl(), ['body' => 'Is this complete?'])->assertOk();
        $thread = Comment::query()->threads()->firstOrFail();

        $this->post($this->commentsUrl(), ['body' => 'Also this', 'parent_id' => $thread->id])->assertOk();
        $this->assertSame(2, Comment::query()->count());

        // Closing a thread is a sign-off (approve permission), which guests never have.
        $this->post(route('comments.resolve', $thread), ['resolved' => 1])->assertForbidden();
        $this->assertNull($thread->fresh()->resolved_at);
    }

    public function test_comment_guest_can_read_existing_threads(): void
    {
        $this->actingAs($this->guestWith(ProjectAccessLevel::Comment));
        $analyst = $this->analystIn($this->a['tenant'], $this->a['workspace']);
        Comment::query()->create([
            'project_id' => $this->a['project']->id,
            'commentable_type' => $this->a['fr']::class,
            'commentable_id' => $this->a['fr']->id,
            'user_id' => $analyst->id,
            'body' => 'Written by the analyst',
            'status' => 'open',
        ]);

        $this->get(route('comments.index', ['model' => 'functional_requirement', 'id' => $this->a['fr']->id]))
            ->assertOk()
            ->assertSee('Written by the analyst');
    }

    public function test_mention_suggestions_do_not_list_unrelated_tenantless_users(): void
    {
        $b = $this->seedTenant('Bravo');
        $colleague = $this->analystIn($this->a['tenant'], $this->a['workspace']);
        $alphaGuest = $this->guestWith(ProjectAccessLevel::Comment, 'Alpha Guest');
        $bravoGuest = User::factory()->guest()->create(['name' => 'Bravo Guest']);
        $this->grant($bravoGuest, $b['project'], ProjectAccessLevel::View);
        $bravoAnalyst = $this->analystIn($b['tenant'], $b['workspace']);
        $this->actingAs($alphaGuest);

        $names = app(CommentService::class)->mentionableUsers($this->a['project']->id)->pluck('name')->all();

        $this->assertContains($colleague->name, $names);
        $this->assertContains('Alpha Guest', $names);
        $this->assertNotContains('Bravo Guest', $names);
        $this->assertNotContains($bravoAnalyst->name, $names);
    }

    public function test_mentioning_a_user_outside_the_project_creates_no_mention(): void
    {
        $b = $this->seedTenant('Bravo');
        $outsider = User::factory()->guest()->create(['name' => 'Zed Outsider']);
        $this->grant($outsider, $b['project'], ProjectAccessLevel::View);
        $this->actingAs($this->guestWith(ProjectAccessLevel::Comment));

        $this->post($this->commentsUrl(), ['body' => 'Hi @Zed.Outsider please look'])->assertOk();

        $this->assertSame(0, \DB::table('comment_mentions')->count());
    }
}
