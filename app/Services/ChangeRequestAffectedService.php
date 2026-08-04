<?php

namespace App\Services;

use App\Models\BusinessNeed;
use App\Models\BusinessObjective;
use App\Models\ChangeRequest;
use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\StakeholderNeed;
use App\Support\ChangeRequestAffectedType;
use Illuminate\Database\Eloquent\Model;

class ChangeRequestAffectedService
{
    /**
     * @return array<int|string, string> id => label
     */
    public function optionsFor(?string $type, ?int $projectId): array
    {
        if ($type === null || $type === '' || $projectId === null || $projectId < 1) {
            return [];
        }

        $class = ChangeRequestAffectedType::modelClass($type);
        if ($class === null) {
            return [];
        }

        $query = $class::query()->where('project_id', $projectId);

        if (in_array(\App\Models\Concerns\HasEntityNumber::class, class_uses_recursive($class), true)) {
            $query->orderBy('number');
        }

        $query->orderBy('title');

        return $query
            ->get()
            ->mapWithKeys(function (Model $item) {
                $code = $item->getAttribute('code');
                $title = (string) ($item->getAttribute('title') ?? $item->getKey());
                $label = trim(($code ? $code.' — ' : '').$title);

                return [$item->getKey() => $label !== '' ? $label : (string) $item->getKey()];
            })
            ->all();
    }

    public function labelFor(?string $type, ?int $id): string
    {
        if ($type === null || $type === '' || $id === null || $id < 1) {
            return '';
        }

        $class = ChangeRequestAffectedType::modelClass($type);
        if ($class === null) {
            return '';
        }

        /** @var Model|null $item */
        $item = $class::query()->find($id);
        if ($item === null) {
            return '';
        }

        $code = $item->getAttribute('code');
        $title = (string) ($item->getAttribute('title') ?? $item->getKey());

        return trim(($code ? $code.' — ' : '').$title);
    }

    public function resolve(ChangeRequest $changeRequest): ?Model
    {
        $type = (string) ($changeRequest->affected_type ?? '');
        $id = (int) ($changeRequest->affected_id ?? 0);
        $class = ChangeRequestAffectedType::modelClass($type);

        if ($class === null || $id < 1) {
            return null;
        }

        return $class::query()->find($id);
    }

    /**
     * Downstream / related items likely tainted by changing the subject.
     *
     * @return list<array{type: string, code: string|null, title: string}>
     */
    public function cascadeFor(ChangeRequest $changeRequest): array
    {
        $subject = $this->resolve($changeRequest);
        if ($subject === null) {
            return [];
        }

        return match (true) {
            $subject instanceof BusinessObjective => $this->cascadeFromObjective($subject),
            $subject instanceof BusinessNeed => $this->cascadeFromNeed($subject),
            $subject instanceof StakeholderNeed => $this->cascadeFromStakeholderNeed($subject),
            $subject instanceof Feature => $this->cascadeFromFeature($subject),
            $subject instanceof FunctionalRequirement => $this->cascadeFromFunctionalRequirement($subject),
            default => [],
        };
    }

    /**
     * @return list<array{type: string, code: string|null, title: string}>
     */
    protected function cascadeFromObjective(BusinessObjective $objective): array
    {
        $rows = [];
        $objective->loadMissing('businessNeeds.stakeholderNeeds.features', 'businessNeeds.stakeholderNeeds.functionalRequirements');

        foreach ($objective->businessNeeds as $need) {
            $rows[] = $this->row(ChangeRequestAffectedType::BUSINESS_NEED, $need);
            foreach ($need->stakeholderNeeds as $sn) {
                $rows[] = $this->row(ChangeRequestAffectedType::STAKEHOLDER_NEED, $sn);
                foreach ($sn->features as $feature) {
                    $rows[] = $this->row(ChangeRequestAffectedType::FEATURE, $feature);
                }
                foreach ($sn->functionalRequirements as $fr) {
                    $rows[] = $this->row(ChangeRequestAffectedType::FUNCTIONAL_REQUIREMENT, $fr);
                }
            }
        }

        return $this->uniqueRows($rows);
    }

    /**
     * @return list<array{type: string, code: string|null, title: string}>
     */
    protected function cascadeFromNeed(BusinessNeed $need): array
    {
        $rows = [];
        $need->loadMissing('stakeholderNeeds.features', 'stakeholderNeeds.functionalRequirements');

        foreach ($need->stakeholderNeeds as $sn) {
            $rows[] = $this->row(ChangeRequestAffectedType::STAKEHOLDER_NEED, $sn);
            foreach ($sn->features as $feature) {
                $rows[] = $this->row(ChangeRequestAffectedType::FEATURE, $feature);
            }
            foreach ($sn->functionalRequirements as $fr) {
                $rows[] = $this->row(ChangeRequestAffectedType::FUNCTIONAL_REQUIREMENT, $fr);
            }
        }

        return $this->uniqueRows($rows);
    }

    /**
     * @return list<array{type: string, code: string|null, title: string}>
     */
    protected function cascadeFromStakeholderNeed(StakeholderNeed $need): array
    {
        $rows = [];
        $need->loadMissing('features.scenarios', 'functionalRequirements');

        foreach ($need->features as $feature) {
            $rows[] = $this->row(ChangeRequestAffectedType::FEATURE, $feature);
            foreach ($feature->scenarios as $scenario) {
                $rows[] = [
                    'type' => 'scenario',
                    'code' => null,
                    'title' => (string) $scenario->title,
                ];
            }
        }
        foreach ($need->functionalRequirements as $fr) {
            $rows[] = $this->row(ChangeRequestAffectedType::FUNCTIONAL_REQUIREMENT, $fr);
        }

        return $this->uniqueRows($rows);
    }

    /**
     * @return list<array{type: string, code: string|null, title: string}>
     */
    protected function cascadeFromFeature(Feature $feature): array
    {
        $rows = [];
        $feature->loadMissing('scenarios');

        foreach ($feature->scenarios as $scenario) {
            $rows[] = [
                'type' => 'scenario',
                'code' => null,
                'title' => (string) $scenario->title,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{type: string, code: string|null, title: string}>
     */
    protected function cascadeFromFunctionalRequirement(FunctionalRequirement $requirement): array
    {
        // FRs are leaf packaging today; cascade is empty unless later linked to Features.
        return [];
    }

    /**
     * @return array{type: string, code: string|null, title: string}
     */
    protected function row(string $type, Model $model): array
    {
        return [
            'type' => $type,
            'code' => $model->getAttribute('code'),
            'title' => (string) ($model->getAttribute('title') ?? $model->getKey()),
        ];
    }

    /**
     * @param  list<array{type: string, code: string|null, title: string}>  $rows
     * @return list<array{type: string, code: string|null, title: string}>
     */
    protected function uniqueRows(array $rows): array
    {
        $seen = [];
        $unique = [];

        foreach ($rows as $row) {
            $key = $row['type'].'|'.($row['code'] ?? '').'|'.$row['title'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $row;
        }

        return $unique;
    }
}
