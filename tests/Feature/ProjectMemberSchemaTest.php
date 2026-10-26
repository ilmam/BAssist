<?php

namespace Tests\Feature;

use App\Models\ProjectMember;
use App\Models\User;
use App\Support\ProjectAccessLevel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsProjectAccess;
use Tests\TestCase;

class ProjectMemberSchemaTest extends TestCase
{
    use RefreshDatabase;
    use SeedsProjectAccess;

    public function test_a_grant_is_unique_per_project_and_user(): void
    {
        $a = $this->seedTenant('Alpha');
        $guest = User::factory()->guest()->create();

        ProjectMember::query()->create(['project_id' => $a['project']->id, 'user_id' => $guest->id, 'access' => ProjectAccessLevel::View]);

        $this->expectException(QueryException::class);
        ProjectMember::query()->create(['project_id' => $a['project']->id, 'user_id' => $guest->id, 'access' => ProjectAccessLevel::Edit]);
    }

    public function test_guest_factory_state_has_no_tenant_or_role(): void
    {
        $guest = User::factory()->guest()->create();

        $this->assertTrue($guest->is_guest);
        $this->assertNull($guest->tenant_id);
        $this->assertNull($guest->role_id);
        $this->assertNull($guest->workspace_id);
        $this->assertFalse(User::factory()->create()->is_guest);
    }

    public function test_access_is_cast_to_enum(): void
    {
        $a = $this->seedTenant('Alpha');
        $guest = User::factory()->guest()->create();
        $this->grant($guest, $a['project'], ProjectAccessLevel::Comment);

        $this->assertSame(ProjectAccessLevel::Comment, $guest->projectMemberships()->first()->access);
    }
}
