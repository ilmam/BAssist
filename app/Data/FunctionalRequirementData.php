<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class FunctionalRequirementData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[Form('text', readonly: true)]
        public ?string $code = null,

        #[ListForm('text')]
        public string $title = '',

        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,

        #[Form('select', 'StakeholderNeed', help: 'Parent lineage: pick a Stakeholder Need OR an approved Change Request (not both).', section: 'traceability', uiSpan: 12)]
        public ?int $stakeholder_need_id = null,

        #[Form('select', 'ChangeRequest', help: 'Approved CRs only. Parent under a CR instead of directly under an SN (CR remains the lasting extension of its SN).', section: 'traceability')]
        public ?int $change_request_id = null,

        #[Form('select', 'SwimlaneFlowStep', help: 'Optional BPD process/decision step this FR elaborates (coverage only).', section: 'traceability')]
        public ?int $swimlane_flow_step_id = null,

        #[Form('textarea', hideQuick: true)]
        public string $statement = '',

        #[Form('textarea', hideQuick: true)]
        public ?string $trigger = null,

        #[Form('textarea', hideQuick: true)]
        public ?string $acceptance_criteria = null,

        #[Form('select', 'Priority')]
        public ?int $priority_id = null,

        #[ListForm('select', 'Status', hideQuick: true)]
        public ?int $status_id = null,
    ) {
    }

    public static function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'stakeholder_need_id' => ['nullable', 'integer', 'exists:stakeholder_needs,id'],
            'change_request_id' => ['nullable', 'integer', 'exists:change_requests,id'],
            'swimlane_flow_step_id' => ['nullable', 'integer', 'exists:swimlane_flow_steps,id'],
            'statement' => ['required', 'string'],
            'trigger' => ['nullable', 'string'],
            'acceptance_criteria' => ['nullable', 'string'],
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
