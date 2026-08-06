<?php

namespace App\Support;

use App\Models\ChangeRequest;
use Illuminate\Validation\ValidationException;

/**
 * FR / Feature lineage: Stakeholder Need is always required.
 * Change Request is an optional approved-change link on top of that SN
 * (CR must be anchored on the same Stakeholder Need).
 */
final class SolutionPackagingParent
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        $snId = isset($data['stakeholder_need_id']) ? (int) $data['stakeholder_need_id'] : 0;
        $crId = isset($data['change_request_id']) ? (int) $data['change_request_id'] : 0;

        $data['change_request_id'] = $crId > 0 ? $crId : null;

        if ($crId > 0) {
            $crNeedId = (int) (ChangeRequest::query()->whereKey($crId)->value('stakeholder_need_id') ?? 0);

            if ($crNeedId <= 0) {
                throw ValidationException::withMessages([
                    'change_request_id' => __('ui.solution_parent_cr_missing_need'),
                ]);
            }

            if ($snId <= 0) {
                $snId = $crNeedId;
            } elseif ($snId !== $crNeedId) {
                throw ValidationException::withMessages([
                    'stakeholder_need_id' => __('ui.solution_parent_cr_need_mismatch'),
                    'change_request_id' => __('ui.solution_parent_cr_need_mismatch'),
                ]);
            }
        }

        if ($snId <= 0) {
            throw ValidationException::withMessages([
                'stakeholder_need_id' => __('ui.solution_parent_need_required'),
            ]);
        }

        $data['stakeholder_need_id'] = $snId;

        return $data;
    }
}
