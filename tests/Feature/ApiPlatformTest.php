<?php

namespace Tests\Feature;

use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
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
use App\Support\ApiTokenAbility;
use App\Support\EntityAccess;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The API platform layer (docs/api-platform.md): personal tokens with
 * read / write abilities, and the derived read-only endpoints. A token acts
 * as its owner, so tenant isolation and role permissions must hold.
 */
class ApiPlatformTest extends TestCase
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

    public function test_requests_without_credentials_are_rejected(): void
    {
        $this->getJson(route('api.projects.readiness', $this->a['project']->id))->assertUnauthorized();
        $this->getJson(route('api.features.gherkin', $this->a['feature']->id))->assertUnauthorized();
    }

    public function test_read_token_can_read_readiness(): void
    {
        $this->usingToken($this->userA, [ApiTokenAbility::READ])
            ->getJson(route('api.projects.readiness', $this->a['project']->id))
            ->assertOk()
            ->assertJsonPath('project.id', $this->a['project']->id)
            ->assertJsonStructure(['total_gaps', 'items', 'severity', 'spine', 'score']);
    }

    public function test_read_only_token_cannot_write_but_write_token_can(): void
    {
        $fr = $this->a['fr'];

        $this->usingToken($this->userA, [ApiTokenAbility::READ])
            ->deleteJson(route('api.functional_requirement.destroy', $fr->id))
            ->assertForbidden();
        $this->assertNotNull(FunctionalRequirement::withoutGlobalScopes()->find($fr->id));

        $this->usingToken($this->userA, [ApiTokenAbility::READ, ApiTokenAbility::WRITE])
            ->deleteJson(route('api.functional_requirement.destroy', $fr->id))
            ->assertOk();
        $this->assertNull(FunctionalRequirement::query()->find($fr->id));
    }

    public function test_expired_token_is_rejected(): void
    {
        $this->usingToken($this->userA, [ApiTokenAbility::READ], now()->subMinute())
            ->getJson(route('api.projects.readiness', $this->a['project']->id))
            ->assertUnauthorized();
    }

    public function test_browser_session_is_not_limited_by_token_abilities(): void
    {
        $this->actingAs($this->userA);

        $this->getJson(route('api.projects.readiness', $this->a['project']->id))->assertOk();
        $this->deleteJson(route('api.functional_requirement.destroy', $this->a['fr']->id))->assertOk();
    }

    public function test_token_cannot_reach_another_tenants_project_or_records(): void
    {
        $foreign = $this->b['project']->id;
        $client = $this->usingToken($this->userA, [ApiTokenAbility::READ]);

        $client->getJson(route('api.projects.readiness', $foreign))->assertNotFound();
        $client->getJson(route('api.projects.traceability', $foreign))->assertNotFound();
        $client->getJson(route('api.projects.acceptance-plan', $foreign))->assertNotFound();
        $client->getJson(route('api.projects.gherkin', $foreign))->assertNotFound();
        $client->getJson(route('api.features.gherkin', $this->b['feature']->id))->assertNotFound();
        $client->getJson(route('api.lineage.show', ['features', $this->b['feature']->id]))->assertNotFound();
    }

    public function test_role_permissions_still_apply_to_tokens(): void
    {
        $role = Role::query()->create(['name' => 'Requirements only', 'slug' => 'requirements-only']);
        RoleEntityPermission::query()->create([
            'role_id' => $role->id,
            'entity' => 'FunctionalRequirement',
            'can_view' => true,
            'can_create' => false,
            'can_update' => false,
            'can_delete' => false,
        ]);
        $limited = User::factory()->create([
            'role_id' => $role->id,
            'tenant_id' => $this->a['tenant']->id,
            'workspace_id' => $this->a['workspace']->id,
        ]);

        $client = $this->usingToken($limited, [ApiTokenAbility::READ, ApiTokenAbility::WRITE]);

        $client->getJson(route('api.projects.readiness', $this->a['project']->id))->assertForbidden();
        $client->getJson(route('api.features.gherkin', $this->a['feature']->id))->assertForbidden();
        $client->getJson(route('api.lineage.show', ['features', $this->a['feature']->id]))->assertForbidden();
        $client->deleteJson(route('api.functional_requirement.destroy', $this->a['fr']->id))->assertForbidden();
    }

    public function test_traceability_returns_rows_summary_and_coverage(): void
    {
        $response = $this->usingToken($this->userA, [ApiTokenAbility::READ])
            ->getJson(route('api.projects.traceability', $this->a['project']->id))
            ->assertOk()
            ->assertJsonStructure(['project', 'summary' => ['total', 'gaps'], 'gap_counts', 'coverage', 'rows']);

        $titles = array_column($response->json('rows'), 'stakeholder_need_title');
        $this->assertContains('Alpha need', $titles);
        $this->assertNotContains('Bravo need', $titles);
    }

    public function test_acceptance_plan_returns_checks(): void
    {
        $response = $this->usingToken($this->userA, [ApiTokenAbility::READ])
            ->getJson(route('api.projects.acceptance-plan', $this->a['project']->id))
            ->assertOk()
            ->assertJsonStructure(['project', 'summary', 'rows']);

        $this->assertContains('Alpha scenario', array_column($response->json('rows'), 'scenario_title'));
    }

    public function test_feature_gherkin_as_json_and_as_text(): void
    {
        $client = $this->usingToken($this->userA, [ApiTokenAbility::READ]);
        $id = $this->a['feature']->id;

        $json = $client->getJson(route('api.features.gherkin', $id))
            ->assertOk()
            ->assertJsonPath('id', $id)
            ->assertJsonPath('scenarios_count', 1)
            ->assertJsonStructure(['code', 'title', 'filename', 'gherkin']);
        $this->assertStringContainsString('Alpha scenario', $json->json('gherkin'));

        $text = $client->get(route('api.features.gherkin', ['id' => $id, 'format' => 'text']))->assertOk();
        $this->assertStringStartsWith('text/plain', (string) $text->headers->get('Content-Type'));
        $this->assertStringContainsString('Alpha scenario', $text->getContent());
    }

    public function test_project_gherkin_lists_every_feature_of_the_project(): void
    {
        $this->usingToken($this->userA, [ApiTokenAbility::READ])
            ->getJson(route('api.projects.gherkin', $this->a['project']->id))
            ->assertOk()
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.id', $this->a['feature']->id);
    }

    public function test_lineage_for_a_spine_record_and_404_for_other_entities(): void
    {
        $client = $this->usingToken($this->userA, [ApiTokenAbility::READ]);

        $client->getJson(route('api.lineage.show', ['features', $this->a['feature']->id]))
            ->assertOk()
            ->assertJsonPath('entity', 'Feature')
            ->assertJsonCount(5, 'lineage.steps')
            ->assertJsonStructure(['parents', 'gaps', 'groups', 'lineage' => ['steps', 'complete', 'total', 'next']]);

        // A real entity with no place on the spine, and a resource that does not exist.
        $client->getJson(route('api.lineage.show', ['projects', $this->a['project']->id]))->assertNotFound();
        $client->getJson(route('api.lineage.show', ['nonsense', 1]))->assertNotFound();
    }

    /**
     * Send following requests with a real personal access token of $user.
     * Guards are forgotten first: the Sanctum guard caches its user for the
     * lifetime of the test application, which would hide a token switch.
     *
     * @param  list<string>  $abilities
     */
    protected function usingToken(User $user, array $abilities, ?DateTimeInterface $expiresAt = null): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('test', $abilities, $expiresAt)->plainTextToken);
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
