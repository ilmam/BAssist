<?php

namespace Database\Seeders;

use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\Project;
use App\Models\Stakeholder;
use App\Models\StakeholderNeed;
use App\Models\User;
use App\Services\SystemStakeholderSeeder;
use App\Services\TenancyProvisioner;
use App\Support\EntityPriority;
use App\Support\EntityStatus;
use Illuminate\Database\Seeder;

class NeedSpineSeeder extends Seeder
{
    public function run(): void
    {
        $provisioner = app(TenancyProvisioner::class);
        $tenant = $provisioner->ensureSharedTenant();
        $workspace = $provisioner->ensureSharedWorkspace($tenant);

        User::query()->whereNull('tenant_id')->each(function (User $user) use ($provisioner) {
            $provisioner->provisionFor($user);
        });

        $agreedId = EntityStatus::id(EntityStatus::AGREED);
        $draftId = EntityStatus::id(EntityStatus::DRAFT);
        $shouldId = EntityPriority::id(EntityPriority::SHOULD);

        $project = Project::query()->updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'code' => 'NS-DEMO',
            ],
            [
                'name' => 'Need Spine Dogfood',
                'description' => 'Internal sample project for exercising the BA spine.',
                'status_id' => $agreedId,
            ],
        );

        app(SystemStakeholderSeeder::class)->seedForProject($project);

        $need = BusinessNeed::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Living spine from needs to stakeholder requirements',
            ],
            [
                'need_type' => 'opportunity',
                'description' => 'Capture needs (why), objectives (what), and stakeholder needs in one cascade.',
                'rationale' => 'Wikis and tickets lose provenance over time.',
                'impact' => 'Delivery work loses strategic alignment.',
                'do_nothing_consequence' => 'Teams keep optimizing tickets without a living need spine.',
            ],
        );

        $objective = BusinessObjective::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Improve delivery traceability',
            ],
            [
                'description' => 'Make need-to-delivery provenance visible across teams.',
                'success_measure' => 'Every active story traces to a business need and objective.',
                'potential_value' => 'Fewer orphan tickets and clearer provenance for audits.',
            ],
        );

        $objective->businessNeeds()->sync([
            $need->id => ['is_primary' => true],
        ]);

        $draftNeed = BusinessNeed::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Unscoped intake from operations (draft)',
            ],
            [
                'need_type' => 'problem',
                'description' => 'Example of need-first drafting before an objective is defined.',
                'rationale' => 'Bottom-up discovery is valid in BABOK Strategy Analysis.',
            ],
        );

        $endUser = Stakeholder::query()
            ->where('project_id', $project->id)
            ->where('system_key', 'end_user')
            ->firstOrFail();

        $customStakeholder = Stakeholder::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'name' => 'Product Owner',
                'is_system' => false,
            ],
            [
                'type' => 'role',
                'status_id' => $agreedId,
                'notes' => 'Example custom stakeholder (Agile). Not a BABOK system default.',
            ],
        );

        $stakeholderNeed = StakeholderNeed::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'See which business need my ask supports',
            ],
            [
                'description' => 'Stakeholders need confidence their requests hang under a real business need.',
                'priority_id' => $shouldId,
                'status_id' => $draftId,
            ],
        );

        $stakeholderNeed->businessObjectives()->sync([$objective->id]);
        $stakeholderNeed->stakeholders()->sync([$endUser->id, $customStakeholder->id]);

        $orphanStakeholderNeed = StakeholderNeed::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'title' => 'Unlinked ask from the floor (orphan)',
            ],
            [
                'description' => 'Example orphan stakeholder need with no business objective or stakeholder yet.',
                'priority_id' => $shouldId,
                'status_id' => $draftId,
            ],
        );
        $orphanStakeholderNeed->businessObjectives()->sync([]);
        $orphanStakeholderNeed->stakeholders()->sync([]);
    }
}
