<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Stakeholder;
use App\Repositories\StakeholderRepository;
use App\Services\TenancyProvisioner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class StakeholderListOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_shows_custom_stakeholders_before_system_defaults(): void
    {
        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);

        $project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => 'Stakeholder Order',
            'code' => 'SHO-'.uniqid(),
        ]);

        Stakeholder::query()->create([
            'project_id' => $project->id,
            'name' => 'Z Custom',
            'is_system' => false,
        ]);
        Stakeholder::query()->create([
            'project_id' => $project->id,
            'name' => 'A Custom',
            'is_system' => false,
        ]);

        $method = new ReflectionMethod(StakeholderRepository::class, 'orderCustomBeforeSystem');
        $method->setAccessible(true);

        /** @var Builder $query */
        $query = $method->invoke(
            new StakeholderRepository,
            Stakeholder::query()->where('project_id', $project->id)
        );

        $rows = $query->get(['id', 'name', 'is_system']);
        $this->assertNotEmpty($rows);

        $sawSystem = false;
        $customNames = [];
        $systemNames = [];

        foreach ($rows as $row) {
            if ($row->is_system) {
                $sawSystem = true;
                $systemNames[] = $row->name;
            } else {
                $this->assertFalse($sawSystem, 'Custom stakeholder appeared after a system stakeholder.');
                $customNames[] = $row->name;
            }
        }

        $this->assertContains('A Custom', $customNames);
        $this->assertContains('Z Custom', $customNames);
        $sortedCustom = $customNames;
        sort($sortedCustom);
        $this->assertSame($sortedCustom, $customNames);

        $sortedSystem = $systemNames;
        sort($sortedSystem);
        $this->assertSame($sortedSystem, $systemNames);
    }
}
