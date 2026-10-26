<?php

namespace Tests\Concerns;

use App\Models\FunctionalRequirement;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Role;
use App\Models\RoleEntityPermission;
use App\Models\StakeholderNeed;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workspace;
use App\Support\ProjectAccessLevel;

trait SeedsProjectAccess
{
    /**
     * @return array{tenant: Tenant, workspace: Workspace, project: Project, need: StakeholderNeed, fr: FunctionalRequirement}
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
        $need = StakeholderNeed::query()->create([
            'project_id' => $project->id,
            'title' => $name.' need',
        ]);
        $fr = FunctionalRequirement::query()->create([
            'project_id' => $project->id,
            'stakeholder_need_id' => $need->id,
            'title' => $name.' requirement',
            'statement' => 'The system shall serve '.$name.'.',
        ]);

        return compact('tenant', 'workspace', 'project', 'need', 'fr');
    }

    /** A second project in an existing workspace, with its own need and requirement. */
    protected function seedSiblingProject(Workspace $workspace, string $name): array
    {
        $project = Project::query()->create([
            'workspace_id' => $workspace->id,
            'name' => $name.' project',
            'code' => strtoupper(substr($name, 0, 3)),
        ]);
        $need = StakeholderNeed::query()->create(['project_id' => $project->id, 'title' => $name.' need']);
        $fr = FunctionalRequirement::query()->create([
            'project_id' => $project->id,
            'stakeholder_need_id' => $need->id,
            'title' => $name.' requirement',
            'statement' => 'The system shall serve '.$name.'.',
        ]);

        return compact('project', 'need', 'fr');
    }

    protected function analystIn(?Tenant $tenant, ?Workspace $workspace, string $slug = 'analyst', bool $canUpdateProject = false): User
    {
        $role = Role::query()->firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);

        foreach (['FunctionalRequirement', 'Project', 'StakeholderNeed', 'StrategicBaseline', 'Architecture'] as $entity) {
            $update = $entity === 'Project' ? $canUpdateProject : true;
            RoleEntityPermission::query()->firstOrCreate(
                ['role_id' => $role->id, 'entity' => $entity],
                ['can_view' => true, 'can_create' => true, 'can_update' => $update, 'can_delete' => true],
            );
        }

        return User::factory()->create([
            'role_id' => $role->id,
            'tenant_id' => $tenant?->id,
            'workspace_id' => $workspace?->id,
        ]);
    }

    /** Someone whose role can update projects, i.e. who may manage members. */
    protected function manager(Tenant $tenant, Workspace $workspace): User
    {
        return $this->analystIn($tenant, $workspace, 'manager', true);
    }

    protected function grant(User $user, Project $project, ProjectAccessLevel $level): ProjectMember
    {
        return ProjectMember::query()->updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $user->id],
            ['access' => $level],
        );
    }
}
