<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\TenancyProvisioner;
use App\Support\EntityAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * Profile page where a user manages their own personal API tokens.
 */
class ApiTokenPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $role = Role::query()->create(['name' => 'Super Admin', 'slug' => EntityAccess::SUPER_ADMIN_SLUG]);
        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);

        $attributes = ['role_id' => $role->id, 'tenant_id' => $tenant->id, 'workspace_id' => $workspace->id];
        $this->user = User::factory()->create($attributes);
        $this->other = User::factory()->create($attributes);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('profile.api-tokens.index'))->assertRedirect(route('login'));
    }

    public function test_page_lists_only_the_users_own_tokens(): void
    {
        $this->user->createToken('Mine');
        $this->other->createToken('Theirs');

        $this->actingAs($this->user)
            ->get(route('profile.api-tokens.index'))
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Theirs');
    }

    public function test_creating_a_token_defaults_to_read_only_and_shows_it_once(): void
    {
        $response = $this->actingAs($this->user)->post(route('profile.api-tokens.store'), [
            'name' => 'CI pipeline',
            'write' => '0',
            'expires_in' => 90,
        ]);

        $response->assertRedirect(route('profile.api-tokens.index'));
        $response->assertSessionHas('new_token');

        $token = $this->user->tokens()->sole();
        $this->assertSame('CI pipeline', $token->name);
        $this->assertSame(['read'], $token->abilities);
        $this->assertTrue($token->expires_at->between(now()->addDays(89), now()->addDays(91)));

        // The plain token is flashed for one page view only.
        $plain = session('new_token');
        $this->get(route('profile.api-tokens.index'))->assertOk()->assertSee($plain);
        $this->get(route('profile.api-tokens.index'))->assertOk()->assertDontSee($plain);
    }

    public function test_write_ability_is_added_only_when_asked(): void
    {
        $this->actingAs($this->user)->post(route('profile.api-tokens.store'), [
            'name' => 'Importer',
            'write' => '1',
            'expires_in' => 30,
        ])->assertSessionHasNoErrors();

        $this->assertSame(['read', 'write'], $this->user->tokens()->sole()->abilities);
    }

    public function test_unsupported_lifetime_is_rejected(): void
    {
        $this->actingAs($this->user)->post(route('profile.api-tokens.store'), [
            'name' => 'Forever',
            'expires_in' => 99999,
        ])->assertSessionHasErrors('expires_in');

        $this->assertSame(0, PersonalAccessToken::query()->count());
    }

    public function test_user_can_revoke_own_token_but_not_someone_elses(): void
    {
        $mine = $this->user->createToken('Mine')->accessToken;
        $theirs = $this->other->createToken('Theirs')->accessToken;

        $this->actingAs($this->user);

        $this->delete(route('profile.api-tokens.destroy', $theirs->id))->assertNotFound();
        $this->assertNotNull(PersonalAccessToken::query()->find($theirs->id));

        $this->delete(route('profile.api-tokens.destroy', $mine->id))
            ->assertRedirect(route('profile.api-tokens.index'));
        $this->assertNull(PersonalAccessToken::query()->find($mine->id));
    }
}
