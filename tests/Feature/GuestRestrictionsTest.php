<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ProjectAccessLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SeedsProjectAccess;
use Tests\TestCase;

class GuestRestrictionsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProjectAccess;

    /** @var array<string, mixed> */
    protected array $a;

    protected User $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->a = $this->seedTenant('Alpha');
        $this->guest = User::factory()->guest()->create();
        $this->grant($this->guest, $this->a['project'], ProjectAccessLevel::Edit);
    }

    public function test_guest_gets_403_on_admin_users(): void
    {
        $this->actingAs($this->guest)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_guest_cannot_open_or_create_api_tokens(): void
    {
        $this->actingAs($this->guest);

        $this->get(route('profile.api-tokens.index'))->assertForbidden();
        $this->post(route('profile.api-tokens.store'), ['name' => 'x', 'expires_in' => 30])->assertForbidden();
        $this->assertSame(0, $this->guest->tokens()->count());
    }

    public function test_a_token_owned_by_a_guest_is_rejected_by_the_api(): void
    {
        Sanctum::actingAs($this->guest, ['read']);

        $this->getJson(route('api.functional_requirement.index'))->assertForbidden();
    }

    public function test_guest_cannot_reach_the_mcp_endpoint(): void
    {
        Sanctum::actingAs($this->guest, ['read', 'write']);

        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertForbidden();
    }

    public function test_a_guest_session_still_drives_the_apps_own_lists(): void
    {
        $this->actingAs($this->guest)
            ->getJson(route('api.functional_requirement.index'))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1);
    }

    public function test_guest_can_open_the_export_pack_and_babok_documents(): void
    {
        $viewer = User::factory()->guest()->create();
        $this->grant($viewer, $this->a['project'], ProjectAccessLevel::View);
        $this->actingAs($viewer);

        $this->get(route('projects.export', $this->a['project']))->assertOk();
        $this->get(route('projects.babok.index', $this->a['project']))->assertOk();
    }
}
