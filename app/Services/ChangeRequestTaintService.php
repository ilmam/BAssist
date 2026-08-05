<?php

namespace App\Services;

use App\Models\ChangeRequest;
use App\Models\Feature;
use App\Models\FunctionalRequirement;
use App\Support\ChangeRequestStatus;
use App\Support\EntityStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeRequestTaintService
{
    /**
     * Agreed FR + BDD Feature candidates under the CR's Stakeholder Need.
     *
     * @return list<array{type: string, id: int, code: string|null, title: string, selected: bool}>
     */
    public function candidatesFor(ChangeRequest $changeRequest): array
    {
        $snId = (int) ($changeRequest->stakeholder_need_id ?? 0);
        if ($snId < 1) {
            return [];
        }

        $agreedId = EntityStatus::id(EntityStatus::AGREED);
        if ($agreedId === null) {
            return [];
        }

        $rows = [];

        foreach (
            FunctionalRequirement::query()
                ->where('stakeholder_need_id', $snId)
                ->where('status_id', $agreedId)
                ->orderBy('number')
                ->get() as $fr
        ) {
            $rows[] = [
                'type' => 'functional_requirement',
                'id' => (int) $fr->id,
                'code' => $fr->code,
                'title' => (string) $fr->title,
                'selected' => true,
            ];
        }

        foreach (
            Feature::query()
                ->where('stakeholder_need_id', $snId)
                ->where('status_id', $agreedId)
                ->orderBy('number')
                ->get() as $feature
        ) {
            $rows[] = [
                'type' => 'feature',
                'id' => (int) $feature->id,
                'code' => $feature->code,
                'title' => (string) $feature->title,
                'selected' => true,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{type?: string, id?: int|string}|string>  $selected
     *         Accepts "functional_requirement:12" / "feature:3" tokens or ['type'=>,'id'=>] arrays.
     */
    public function approveAndTaint(ChangeRequest $changeRequest, array $selected): ChangeRequest
    {
        if (! $changeRequest->hasStakeholderNeed()) {
            throw ValidationException::withMessages([
                'stakeholder_need_id' => __('ui.change_request_affected_required'),
            ]);
        }

        if ((string) $changeRequest->status === ChangeRequestStatus::APPROVED
            || (string) $changeRequest->status === ChangeRequestStatus::IMPLEMENTED) {
            throw ValidationException::withMessages([
                'status' => __('ui.change_request_already_approved'),
            ]);
        }

        $needRevisionId = EntityStatus::id(EntityStatus::NEED_REVISION);
        if ($needRevisionId === null) {
            throw ValidationException::withMessages([
                'status' => __('ui.change_request_need_revision_missing'),
            ]);
        }

        $tokens = $this->normalizeSelected($selected);
        $allowed = collect($this->candidatesFor($changeRequest))
            ->mapWithKeys(fn (array $row) => [$row['type'].':'.$row['id'] => $row]);

        foreach ($tokens as $token) {
            if (! $allowed->has($token)) {
                throw ValidationException::withMessages([
                    'taint_items' => __('ui.change_request_taint_invalid'),
                ]);
            }
        }

        return DB::transaction(function () use ($changeRequest, $tokens, $needRevisionId) {
            foreach ($tokens as $token) {
                [$type, $id] = explode(':', $token, 2);
                $id = (int) $id;

                if ($type === 'functional_requirement') {
                    FunctionalRequirement::query()
                        ->whereKey($id)
                        ->update(['status_id' => $needRevisionId]);
                } elseif ($type === 'feature') {
                    Feature::query()
                        ->whereKey($id)
                        ->update(['status_id' => $needRevisionId]);
                }
            }

            $changeRequest->status = ChangeRequestStatus::APPROVED;
            $changeRequest->save();

            return $changeRequest->fresh(['stakeholderNeed', 'project', 'priority']);
        });
    }

    /**
     * @param  list<mixed>  $selected
     * @return list<string>
     */
    protected function normalizeSelected(array $selected): array
    {
        $tokens = [];

        foreach ($selected as $item) {
            if (is_string($item) && str_contains($item, ':')) {
                $tokens[] = $item;
                continue;
            }

            if (is_array($item) && isset($item['type'], $item['id'])) {
                $tokens[] = (string) $item['type'].':'.(int) $item['id'];
            }
        }

        return array_values(array_unique($tokens));
    }
}
