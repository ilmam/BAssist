<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ProjectAccessLevel;
use App\Support\ProjectContext;
use App\Support\WorkspaceContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsProjectAccess;
use Tests\TestCase;

class GuestSessionTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProjectAccess;

    public function test_authenticated_guest_stays_tenantless(): void
    {
        config(['tenancy.auto_assign' => true, 'tenancy.mode' => 'shared']);
        $a = $this->seedTenant('Alpha');
        $guest = User::factory()->guest()->create();
        $this->grant($guest, $a['project'], ProjectAccessLevel::View);

        $this->actingAs($guest)->get(route('home'));

        $this->assertNull($guest->fresh()->tenant_id);
        $this->assertNull($guest->fresh()->workspace_id);
    }

    public function test_project_context_accepts_a_granted_project_and_ignores_others(): void
    {
        $a = $this->seedTenant('Alpha');
        $b = $this->seedTenant('Bravo');
        $guest = User::factory()->guest()->create();
        $this->grant($guest, $a['project'], ProjectAccessLevel::View);
        $this->actingAs($guest);
        $context = app(ProjectContext::class);

        $context->set($a['project']->id);
        $this->assertSame($a['project']->id, $context->id());
        $this->assertSame($a['workspace']->id, app(WorkspaceContext::class)->id());

        $context->set($b['project']->id);
        $this->assertNull($context->id());
    }

    public function test_workspace_context_accepts_the_granted_workspace_only(): void
    {
        $a = $this->seedTenant('Alpha');
        $b = $this->seedTenant('Bravo');
        $guest = User::factory()->guest()->create();
        $this->grant($guest, $a['project'], ProjectAccessLevel::View);
        $this->actingAs($guest);
        $context = app(WorkspaceContext::class);

        $context->set($a['workspace']->id);
        $this->assertSame($a['workspace']->id, $context->id());

        $context->set($b['workspace']->id);
        $this->assertNull($context->id());
    }
}
