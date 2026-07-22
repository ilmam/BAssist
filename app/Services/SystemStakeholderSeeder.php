<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Stakeholder;
use App\Support\EntityStatus;

class SystemStakeholderSeeder
{
    public function seedForProject(Project $project): void
    {
        $keys = [];
        $agreedId = EntityStatus::id(EntityStatus::AGREED);
        $deprecatedId = EntityStatus::id(EntityStatus::DEPRECATED);

        foreach (config('stakeholders.system', []) as $definition) {
            $keys[] = $definition['key'];

            Stakeholder::query()->updateOrCreate(
                [
                    'project_id' => $project->id,
                    'system_key' => $definition['key'],
                ],
                [
                    'name' => $definition['name'],
                    'type' => $definition['type'] ?? 'role',
                    'is_system' => true,
                    'status_id' => $agreedId,
                ],
            );
        }

        Stakeholder::query()
            ->where('project_id', $project->id)
            ->where('is_system', true)
            ->whereNotNull('system_key')
            ->whereNotIn('system_key', $keys)
            ->each(function (Stakeholder $stakeholder) use ($deprecatedId): void {
                $stakeholder->forceFill([
                    'status_id' => $deprecatedId,
                    'is_system' => false,
                    'system_key' => null,
                ])->saveQuietly();

                $stakeholder->delete();
            });
    }
}
