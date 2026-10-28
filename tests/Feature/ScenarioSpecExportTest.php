<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\Project;
use App\Models\Scenario;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workspace;
use App\Services\GherkinFeatureAssembler;
use App\Support\ApiTokenAbility;
use App\Support\EntityAccess;
use App\Support\EntityStatus;
use Database\Seeders\StatusPrioritySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The exported Gherkin is the spec a code project builds and tests against, so it
 * must be tamper-evident: every scenario carries a stable code, its approval state
 * and a revision hash of its content. A pulled file that was edited locally no
 * longer matches what BAssist exports.
 */
class ScenarioSpecExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_scenarios_are_numbered_per_project_and_expose_a_code(): void
    {
        [$projectA, $featureA] = $this->seedProject('Alpha');
        [, $featureB] = $this->seedProject('Bravo');

        $first = $this->scenario($featureA, 'First');
        $second = $this->scenario($featureA, 'Second');
        $otherProject = $this->scenario($featureB, 'Elsewhere');

        $this->assertSame('SC-1', $first->code);
        $this->assertSame('SC-2', $second->code);
        $this->assertSame('SC-1', $otherProject->code, 'numbering restarts in another project');

        $second->delete();
        $this->assertSame('SC-3', $this->scenario($featureA, 'Third')->code, 'a number is never reused');
    }

    public function test_export_tags_each_scenario_with_code_status_and_revision(): void
    {
        [, $feature] = $this->seedProject('Alpha');
        $scenario = $this->scenario($feature, 'Quote');
        $scenario->update(['status_id' => EntityStatus::id(EntityStatus::AGREED)]);

        $gherkin = $this->assembled($feature);

        $this->assertMatchesRegularExpression(
            '/^@SC-1 @status:agreed @rev:[0-9a-f]{12}\nScenario: Quote$/m',
            $gherkin
        );
    }

    public function test_revision_follows_content_only_not_status(): void
    {
        [, $feature] = $this->seedProject('Alpha');
        $scenario = $this->scenario($feature, 'Quote');
        $assembler = app(GherkinFeatureAssembler::class);

        $before = $assembler->scenarioRevision($scenario);

        $scenario->update(['status_id' => EntityStatus::id(EntityStatus::AGREED)]);
        $this->assertSame($before, $assembler->scenarioRevision($scenario->fresh()), 'approving does not change the content');

        $scenario->update(['body' => str_replace('answered', 'ignored', $scenario->body)]);
        $this->assertNotSame($before, $assembler->scenarioRevision($scenario->fresh()), 'editing a step does');
    }

    public function test_revision_ignores_system_tags_and_line_endings(): void
    {
        [, $feature] = $this->seedProject('Alpha');
        $scenario = $this->scenario($feature, 'Quote');
        $assembler = app(GherkinFeatureAssembler::class);
        $plain = $assembler->scenarioRevision($scenario);

        $scenario->body = "@SC-1 @status:draft @rev:aaaaaaaaaaaa\r\n".str_replace("\n", "\r\n", $scenario->body);

        $this->assertSame($plain, $assembler->scenarioRevision($scenario));
    }

    public function test_stale_system_tags_are_replaced_and_authored_tags_are_kept(): void
    {
        [, $feature] = $this->seedProject('Alpha');
        $scenario = $this->scenario($feature, 'Quote');
        $scenario->update(['body' => "@smoke @SC-99 @status:agreed @rev:000000000000\n".$scenario->body]);

        $gherkin = $this->assembled($feature);

        $this->assertSame(1, substr_count($gherkin, '@SC-'));
        $this->assertStringNotContainsString('@SC-99', $gherkin);
        $this->assertStringNotContainsString('@rev:000000000000', $gherkin);
        $this->assertMatchesRegularExpression('/^@SC-1 @status:draft @rev:[0-9a-f]{12} @smoke$/m', $gherkin);
    }

    public function test_saving_a_scenario_strips_system_tags_from_the_stored_body(): void
    {
        [, $feature] = $this->seedProject('Alpha');
        $scenario = $this->scenario($feature, 'Quote');
        $scenario->body = "@SC-1 @status:agreed @rev:abcabcabcabc @smoke\n".$scenario->body;

        $scenario->syncDocumentFields();

        $this->assertStringNotContainsString('@SC-1', $scenario->body);
        $this->assertStringNotContainsString('@status:', $scenario->body);
        $this->assertStringNotContainsString('@rev:', $scenario->body);
        $this->assertStringContainsString('@smoke', $scenario->body);
    }

    public function test_feature_document_lists_scenarios_and_a_file_revision(): void
    {
        [$project, $feature] = $this->seedProject('Alpha');
        $this->scenario($feature, 'Quote');
        $client = $this->apiClientFor($project);

        $document = $client->getJson(route('api.features.gherkin', $feature->id))->assertOk()->json();

        $this->assertSame('draft', $document['status']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{12}$/', $document['rev']);
        $this->assertCount(1, $document['scenarios']);
        $this->assertSame('SC-1', $document['scenarios'][0]['code']);
        $this->assertSame('draft', $document['scenarios'][0]['status']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{12}$/', $document['scenarios'][0]['rev']);

        $this->assertStringContainsString('@SC-1 @status:draft @rev:'.$document['scenarios'][0]['rev'], $document['gherkin']);

        $this->scenario($feature, 'Another');
        $changed = $client->getJson(route('api.features.gherkin', $feature->id))->assertOk()->json();
        $this->assertNotSame($document['rev'], $changed['rev']);
    }

    protected function apiClientFor(Project $project): static
    {
        $role = Role::query()->create(['name' => 'Super Admin', 'slug' => EntityAccess::SUPER_ADMIN_SLUG]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'tenant_id' => $project->workspace->tenant_id,
            'workspace_id' => $project->workspace_id,
        ]);
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('test', [ApiTokenAbility::READ])->plainTextToken);
    }

    /** @return array{0: Project, 1: Feature} */
    protected function seedProject(string $name): array
    {
        $this->seed(StatusPrioritySeeder::class);
        EntityStatus::forgetCache();

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
        $feature = Feature::query()->create([
            'project_id' => $project->id,
            'title' => $name.' feature',
            'body' => 'Feature: '.$name." feature\n",
        ]);

        return [$project, $feature];
    }

    protected function scenario(Feature $feature, string $title): Scenario
    {
        return Scenario::query()->create([
            'feature_id' => $feature->id,
            'title' => $title,
            'body' => 'Scenario: '.$title."\n  Given a dealer\n  When they ask\n  Then they are answered\n",
        ]);
    }

    protected function assembled(Feature $feature): string
    {
        return app(GherkinFeatureAssembler::class)->assembleFeature($feature->fresh());
    }
}
