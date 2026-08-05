<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * FR / Feature lineage: exclusive parent — Stakeholder Need XOR approved Change Request.
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

        if ($snId > 0 && $crId > 0) {
            throw ValidationException::withMessages([
                'stakeholder_need_id' => __('ui.solution_parent_exclusive'),
                'change_request_id' => __('ui.solution_parent_exclusive'),
            ]);
        }

        $data['stakeholder_need_id'] = $snId > 0 ? $snId : null;
        $data['change_request_id'] = $crId > 0 ? $crId : null;

        return $data;
    }
}
