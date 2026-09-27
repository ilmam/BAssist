<?php

namespace Tests\Unit;

use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\Feature;
use App\Models\Project;
use App\Models\Scenario;
use App\Models\StakeholderNeed;
use App\Models\User;
use App\Repositories\ScenarioRepository;
use App\Services\FeatureImportService;
use App\Services\ProjectReadinessService;
use App\Services\TenancyProvisioner;
use App\Services\TraceabilityMatrixService;
use App\Support\EntityPriority;
use App\Support\EntityStatus;
use Database\Seeders\StatusPrioritySeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ScenarioCoveringNeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_covering_scenario_packages_a_second_stakeholder_need_in_the_matrix(): void
    {
        [$project, $ownerNeed, $coveredNeed, $feature, $scenario] = $this->seedCoveringExample();

        $matrix = app(TraceabilityMatrixService::class)->build(['project_id' => $project->id]);
        $coveredRows = collect($matrix['rows'])->where('stakeholder_need_id', $coveredNeed->id);
        $ownerRows = collect($matrix['rows'])->where('stakeholder_need_id', $ownerNeed->id);

        $this->assertTrue($coveredRows->isNotEmpty());
        $this->assertFalse($coveredRows->contains(fn (array $row) => in_array('missing_feature', $row['gaps'], true)));
        $this->assertTrue($coveredRows->contains(
            fn (array $row) => (int) ($row['scenario_id'] ?? 0) === (int) $scenario->id
                && (int) ($row['feature_id'] ?? 0) === (int) $feature->id
        ));
        $this->assertTrue($ownerRows->contains(
            fn (array $row) => (int) ($row['feature_id'] ?? 0) === (int) $feature->id
                && empty($row['scenario_id'])
        ));
    }

    public function test_mapping_a_scenario_to_its_feature_parent_need_does_not_duplicate_rows(): void
    {
        [, $ownerNeed, , $feature, $scenario] = $this->seedCoveringExample();
        $scenario->update(['stakeholder_need_id' => $ownerNeed->id]);

        $matrix = app(TraceabilityMatrixService::class)->build(['project_id' => $feature->project_id]);
        $ownerRows = collect($matrix['rows'])->where('stakeholder_need_id', $ownerNeed->id);

        $this->assertCount(1, $ownerRows->where('feature_id', $feature->id));
        $this->assertTrue($ownerRows->every(fn (array $row) => empty($row['scenario_id'])));
    }

    public function test_feature_import_preserves_covering_need_by_scenario_title(): void
    {
        [, , $coveredNeed, $feature, $scenario] = $this->seedCoveringExample();

        $source = <<<GHERKIN
Feature: Bid on a live vehicle auction

Scenario: {$scenario->title}
  Given the vehicle is open for bidding
GHERKIN;

        $updated = (new FeatureImportService)->applyReplace($feature->fresh(['scenarios']), $source);
        $replaced = $updated->scenarios->firstWhere('title', $scenario->title);

        $this->assertNotNull($replaced);
        $this->assertNotSame($scenario->id, $replaced->id);
        $this->assertSame($coveredNeed->id, $replaced->stakeholder_need_id);
    }

    public function test_wont_need_is_shown_without_a_missing_feature_gap(): void
    {
        [$project, $wontNeed] = $this->seedUnpackagedNeed(EntityPriority::WONT, EntityStatus::AGREED);

        $matrix = app(TraceabilityMatrixService::class)->build(['project_id' => $project->id]);
        $rows = collect($matrix['rows'])->where('stakeholder_need_id', $wontNeed->id);

        $this->assertTrue($rows->isNotEmpty());
        $this->assertTrue($rows->every(fn (array $row) => ! in_array('missing_feature', $row['gaps'], true)));
        $this->assertTrue($rows->every(fn (array $row) => ($row['deferred_this_release'] ?? false) === true));
    }

    public function test_deprecated_need_is_shown_without_a_missing_feature_gap(): void
    {
        [$project, $deprecatedNeed] = $this->seedUnpackagedNeed(EntityPriority::SHOULD, EntityStatus::DEPRECATED);

        $matrix = app(TraceabilityMatrixService::class)->build(['project_id' => $project->id]);
        $rows = collect($matrix['rows'])->where('stakeholder_need_id', $deprecatedNeed->id);

        $this->assertTrue($deprecatedNeed->isDeprecated());
        $this->assertTrue($rows->isNotEmpty());
        $this->assertTrue($rows->every(fn (array $row) => ! in_array('missing_feature', $row['gaps'], true)));
        $this->assertTrue($rows->every(fn (array $row) => ($row['deferred_this_release'] ?? false) === true));
    }

    public function test_readiness_does_not_count_wont_needs_as_unpackaged(): void
    {
        (new SuperAdminSeeder)->run();
        $this->actingAs(User::query()->where('email', config('auth.super_admin.email'))->firstOrFail());

        [$project, $wontNeed] = $this->seedUnpackagedNeed(EntityPriority::WONT, EntityStatus::AGREED);
        $inReleaseNeed = $this->seedUnpackagedNeedOnProject($project, EntityPriority::MUST, EntityStatus::DRAFT);

        $readiness = app(ProjectReadinessService::class)->forProject($project);
        $gap = collect($readiness['items'])->firstWhere('key', 'stories_without_features');
        $packaging = collect($readiness['spine'])->firstWhere('key', 'packaging');

        $this->assertNotNull($gap);
        $this->assertSame(1, $gap['count']);
        $this->assertNotNull($packaging);
        $this->assertSame(2, $packaging['total']);
        $this->assertSame(1, $packaging['ready']);
        $this->assertTrue($wontNeed->isOutOfThisRelease());
        $this->assertFalse($inReleaseNeed->isOutOfThisRelease());
    }

    public function test_covering_need_must_share_the_feature_project(): void
    {
        [, , , $feature] = $this->seedCoveringExample();
        $otherProject = Project::query()->create([
            'workspace_id' => $feature->project->workspace_id,
            'name' => 'Other',
            'code' => 'OTH-'.uniqid(),
        ]);
        $foreignNeed = StakeholderNeed::query()->create([
            'project_id' => $otherProject->id,
            'title' => 'Foreign need',
        ]);

        $this->expectException(ValidationException::class);

        (new ScenarioRepository)->create([
            'title' => 'Another example',
            'feature_id' => $feature->id,
            'body' => "Scenario: Another example\n  Given x\n",
            'stakeholder_need_id' => $foreignNeed->id,
        ]);
    }

    /**
     * @return array{0: Project, 1: StakeholderNeed, 2: StakeholderNeed, 3: Feature, 4: Scenario}
     */
    protected function seedCoveringExample(): array
    {
        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);

        $project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Covering Scenario Test',
            'code' => 'CST-'.uniqid(),
        ]);

        $businessNeed = BusinessNeed::query()->create([
            'project_id' => $project->id,
            'title' => 'Sell through auction',
        ]);
        $objective = BusinessObjective::query()->create([
            'project_id' => $project->id,
            'title' => 'Transparent bidding',
        ]);
        $objective->businessNeeds()->sync([$businessNeed->id => ['is_primary' => true]]);

        $ownerNeed = StakeholderNeed::query()->create([
            'project_id' => $project->id,
            'title' => 'Defined bidding steps',
        ]);
        $coveredNeed = StakeholderNeed::query()->create([
            'project_id' => $project->id,
            'title' => 'Best bid shared among dealers',
        ]);
        $ownerNeed->businessObjectives()->attach($objective->id);
        $coveredNeed->businessObjectives()->attach($objective->id);

        $feature = Feature::query()->create([
            'project_id' => $project->id,
            'stakeholder_need_id' => $ownerNeed->id,
            'title' => 'Bid on a live vehicle auction',
            'body' => "Feature: Bid on a live vehicle auction\n",
        ]);
        $scenario = Scenario::query()->create([
            'feature_id' => $feature->id,
            'stakeholder_need_id' => $coveredNeed->id,
            'title' => 'The current highest bid is visible and the bidder identity is anonymous',
            'body' => "Scenario: The current highest bid is visible and the bidder identity is anonymous\n  Then the current highest bid should be visible\n",
        ]);

        return [$project, $ownerNeed, $coveredNeed, $feature->load('project'), $scenario];
    }

    /**
     * @return array{0: Project, 1: StakeholderNeed}
     */
    protected function seedUnpackagedNeed(string $priority, string $status): array
    {
        (new StatusPrioritySeeder)->run();

        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);

        $project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Deferral Test',
            'code' => 'DFT-'.uniqid(),
        ]);

        return [$project, $this->seedUnpackagedNeedOnProject($project, $priority, $status)];
    }

    protected function seedUnpackagedNeedOnProject(Project $project, string $priority, string $status): StakeholderNeed
    {
        $businessNeed = BusinessNeed::query()->create([
            'project_id' => $project->id,
            'title' => 'Need '.$priority.' '.$status,
        ]);
        $objective = BusinessObjective::query()->create([
            'project_id' => $project->id,
            'title' => 'Objective '.$priority.' '.$status,
        ]);
        $objective->businessNeeds()->sync([$businessNeed->id => ['is_primary' => true]]);

        $priorityId = EntityPriority::id($priority);
        $statusId = EntityStatus::id($status);
        $this->assertNotNull($priorityId);
        $this->assertNotNull($statusId);

        $stakeholderNeed = StakeholderNeed::query()->create([
            'project_id' => $project->id,
            'title' => 'Story '.$priority.' '.$status,
            'priority_id' => $priorityId,
            'status_id' => $statusId,
        ]);
        $stakeholderNeed->businessObjectives()->attach($objective->id);

        return $stakeholderNeed->fresh();
    }
}
