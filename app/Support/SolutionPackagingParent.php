<?php

namespace App\Support;

use App\Models\ChangeRequest;
use Illuminate\Validation\ValidationException;

/**
 * FR / Feature lineage: exclusive parent — Stakeholder Need XOR Change Request.
 * At least one is required. Approved/implemented CRs only when CR is chosen.
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

        $data['stakeholder_need_id'] = $snId > 0 ? $snId : null;
        $data['change_request_id'] = $crId > 0 ? $crId : null;

        if ($snId > 0 && $crId > 0) {
            throw ValidationException::withMessages([
                'stakeholder_need_id' => __('ui.solution_parent_exclusive'),
                'change_request_id' => __('ui.solution_parent_exclusive'),
            ]);
        }

        if ($snId <= 0 && $crId <= 0) {
            throw ValidationException::withMessages([
                'stakeholder_need_id' => __('ui.solution_parent_need_required'),
                'change_request_id' => __('ui.solution_parent_need_required'),
            ]);
        }

        if ($crId > 0) {
            $cr = ChangeRequest::query()
                ->whereKey($crId)
                ->first(['id', 'stakeholder_need_id', 'status']);

            if ($cr === null || (int) ($cr->stakeholder_need_id ?? 0) <= 0) {
                throw ValidationException::withMessages([
                    'change_request_id' => __('ui.solution_parent_cr_missing_need'),
                ]);
            }

            if (! in_array((string) $cr->status, [ChangeRequestStatus::APPROVED, ChangeRequestStatus::IMPLEMENTED], true)) {
                throw ValidationException::withMessages([
                    'change_request_id' => __('ui.solution_parent_cr_not_approved'),
                ]);
            }
        }

        return $data;
    }
}
