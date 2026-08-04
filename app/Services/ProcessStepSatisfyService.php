<?php

namespace App\Services;

use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Support\ProcessStepSatisfyType;
use Illuminate\Database\Eloquent\Model;

class ProcessStepSatisfyService
{
    /**
     * Combined FR + Feature options for a project (select value = type:id).
     *
     * @return list<array{value: string, label: string}>
     */
    public function optionsForProject(?int $projectId): array
    {
        if ($projectId === null || $projectId < 1) {
            return [];
        }

        $options = [];

        foreach ($this->requirements(FunctionalRequirement::class, $projectId) as $item) {
            $options[] = [
                'value' => ProcessStepSatisfyType::encode(ProcessStepSatisfyType::FUNCTIONAL_REQUIREMENT, $item->getKey()),
                'label' => $this->itemLabel($item),
            ];
        }

        foreach ($this->requirements(Feature::class, $projectId) as $item) {
            $options[] = [
                'value' => ProcessStepSatisfyType::encode(ProcessStepSatisfyType::FEATURE, $item->getKey()),
                'label' => $this->itemLabel($item),
            ];
        }

        return $options;
    }

    public function labelFor(?string $type, ?int $id): string
    {
        if (! ProcessStepSatisfyType::isValid($type) || $id === null || $id < 1) {
            return '';
        }

        $class = ProcessStepSatisfyType::modelClass((string) $type);
        if ($class === null) {
            return '';
        }

        /** @var Model|null $item */
        $item = $class::query()->find($id);

        return $item ? $this->itemLabel($item) : '';
    }

    /**
     * @param  class-string<Model>  $class
     * @return \Illuminate\Support\Collection<int, Model>
     */
    protected function requirements(string $class, int $projectId)
    {
        return $class::query()
            ->where('project_id', $projectId)
            ->orderBy('number')
            ->orderBy('title')
            ->get();
    }

    protected function itemLabel(Model $item): string
    {
        $code = $item->getAttribute('code');
        $title = (string) ($item->getAttribute('title') ?? $item->getKey());

        return trim(($code ? $code.' — ' : '').$title);
    }
}
