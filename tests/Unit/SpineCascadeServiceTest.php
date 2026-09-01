<?php

namespace Tests\Unit;

use App\Data\BusinessObjectiveData;
use App\Data\FeatureData;
use App\Http\Controllers\FeatureController;
use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\Feature;
use App\Models\Project;
use App\Models\StakeholderNeed;
use App\Services\SpineCascadeService;
use App\Services\TenancyProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SpineCascadeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_entity_has_no_cascade(): void
    {
        $this->assertNull(app(SpineCascadeService::class)->for('Risk', 1));
    }

    public function test_need_without_objectives_exposes_gap_and_create_url(): void
    {
        $need = $this->seedNeed();

        $cascade = app(SpineCascadeService::class)->for('BusinessNeed', $need->id);

        $this->assertNotNull($cascade);
        $this->assertSame([], $cascade['parents']);
        $this->assertSame(['no_objectives'], array_column($cascade['gaps'], 'key'));
        $this->assertStringContainsString('primary_business_need_id='.$need->id, $cascade['groups'][0]['add_url']);
        $this->assertSame([], $cascade['groups'][0]['items']);
    }

    public function test_stakeholder_need_shows_ancestors_children_and_packaging_gap(): void
    {
        [$need, $objective, $story] = $this->seedNeedObjectiveStory();

        $cascade = app(SpineCascadeService::class)->for('StakeholderNeed', $story->id);

        $this->assertNotNull($cascade);
        $this->assertStringContainsString($need->code, $cascade['parents'][0]['label']);
        $this->assertStringContainsString($objective->code, $cascade['parents'][1]['label']);
        $this->assertSame([], array_column($cascade['gaps'], 'key'));
        $this->assertCount(1, $cascade['groups']);
        $this->assertSame('packaging', $cascade['groups'][0]['key']);
        $this->assertSame(
            ['FunctionalRequirement', 'Feature', 'NonFunctionalRequirement'],
            array_column($cascade['groups'][0]['add_actions'], 'model')
        );
        $this->assertStringContainsString('stakeholder_need_id='.$story->id, $cascade['groups'][0]['add_actions'][0]['url']);
    }

    public function test_feature_parent_strip_and_scenario_gap(): void
    {
        [, , $story] = $this->seedNeedObjectiveStory();
        $feature = Feature::query()->create([
            'project_id' => $story->project_id,
            'stakeholder_need_id' => $story->id,
            'title' => 'Submit inquiry',
        ]);

        $cascade = app(SpineCascadeService::class)->for('Feature', $feature->id);

        $this->assertNotNull($cascade);
        $this->assertSame($story->code.' — '.$story->title, $cascade['parents'][2]['label']);
        $this->assertSame($story->code, $cascade['parents'][2]['code']);
        $this->assertSame($story->title, $cascade['parents'][2]['title']);
        $this->assertSame(['no_scenarios'], array_column($cascade['gaps'], 'key'));
        $this->assertSame([], $cascade['groups']);
        $this->assertStringContainsString('feature_id='.$feature->id, $cascade['gaps'][0]['action_url']);
    }

    public function test_create_forms_prefill_parent_ids_from_query(): void
    {
        $request = Request::create('/features/modal/create', 'GET', [
            'stakeholder_need_id' => 12,
            'primary_business_need_id' => 4,
        ]);
        $this->app->instance('request', $request);

        $controller = app(FeatureController::class);
        $method = new \ReflectionMethod(FeatureController::class, 'applyStickyContextDefaults');
        $method->setAccessible(true);

        $feature = $method->invoke($controller, FeatureData::from(FeatureData::empty()));
        $this->assertSame(12, $feature->stakeholder_need_id);

        $objective = $method->invoke($controller, BusinessObjectiveData::from(BusinessObjectiveData::empty()));
        $this->assertSame(4, $objective->primary_business_need_id);
    }

    public function test_spine_details_and_view_modals_include_cascade_partial(): void
    {
        $files = [
            'resources/views/pages/business_needs/details.blade.php',
            'resources/views/pages/business_needs/modals/view.blade.php',
            'resources/views/pages/business_objectives/details.blade.php',
            'resources/views/pages/business_objectives/modals/view.blade.php',
            'resources/views/pages/stakeholder_needs/details.blade.php',
            'resources/views/pages/stakeholder_needs/modals/view.blade.php',
            'resources/views/pages/functional_requirements/details.blade.php',
            'resources/views/pages/functional_requirements/modals/view.blade.php',
            'resources/views/pages/non_functional_requirements/details.blade.php',
            'resources/views/pages/non_functional_requirements/modals/view.blade.php',
            'resources/views/pages/features/partials/view-content.blade.php',
            'resources/views/pages/scenarios/partials/view-content.blade.php',
        ];

        foreach ($files as $relative) {
            $contents = file_get_contents(dirname(__DIR__, 2).'/'.$relative);
            $this->assertIsString($contents);
            $this->assertStringContainsString('pages.partials.spine-cascade', $contents, $relative);
        }

        $partial = file_get_contents(dirname(__DIR__, 2).'/resources/views/pages/partials/spine-cascade.blade.php');
        $this->assertIsString($partial);
        $this->assertStringContainsString('spine-breadcrumb', $partial);
        $this->assertStringContainsString('aria-current="page"', $partial);
        $this->assertStringContainsString('add_actions', $partial);
        $this->assertStringContainsString('spine-cascade-add', $partial);
    }

    protected function seedNeed(): BusinessNeed
    {
        $project = $this->seedProject();

        return BusinessNeed::query()->create([
            'project_id' => $project->id,
            'title' => 'Manual inquiries are slow',
        ]);
    }

    /**
     * @return array{0: BusinessNeed, 1: BusinessObjective, 2: StakeholderNeed}
     */
    protected function seedNeedObjectiveStory(): array
    {
        $need = $this->seedNeed();
        $objective = BusinessObjective::query()->create([
            'project_id' => $need->project_id,
            'title' => 'Cut wait time',
        ]);
        $objective->businessNeeds()->sync([$need->id => ['is_primary' => true]]);

        $story = StakeholderNeed::query()->create([
            'project_id' => $need->project_id,
            'title' => 'Dealer can submit an inquiry',
        ]);
        $story->businessObjectives()->attach($objective->id);

        return [$need->fresh(), $objective->fresh(), $story->fresh()];
    }

    protected function seedProject(): Project
    {
        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);

        return Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Cascade Test',
            'code' => 'CAS-'.uniqid(),
        ]);
    }
}
