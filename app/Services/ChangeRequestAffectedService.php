<?php

namespace App\Services;

use App\Models\ChangeRequest;
use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Models\StakeholderNeed;
use App\Support\ChangeRequestAffectedType;
use Illuminate\Database\Eloquent\Model;

/**
 * Cascade / label helpers for CR → SN anchoring.
 */
class ChangeRequestAffectedService
{
    public function labelForStakeholderNeed(?int $id): string
    {
        if ($id === null || $id < 1) {
            return '';
        }

        /** @var StakeholderNeed|null $item */
        $item = StakeholderNeed::query()->find($id);
        if ($item === null) {
            return '';
        }

        $code = $item->getAttribute('code');
        $title = (string) ($item->getAttribute('title') ?? $item->getKey());

        return trim(($code ? $code.' — ' : '').$title);
    }

    /**
     * Downstream / related items likely tainted by changing the subject SN.
     *
     * @return list<array{type: string, code: string|null, title: string}>
     */
    public function cascadeFor(ChangeRequest $changeRequest): array
    {
        $snId = (int) ($changeRequest->stakeholder_need_id ?? 0);
        if ($snId < 1) {
            return [];
        }

        /** @var StakeholderNeed|null $need */
        $need = StakeholderNeed::query()->find($snId);
        if ($need === null) {
            return [];
        }

        return $this->cascadeFromStakeholderNeed($need);
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
