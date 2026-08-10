<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class FeatureData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[Form('text', readonly: true)]
        public ?string $code = null,

        #[ListForm('text', help: 'List label. Synced from the Feature: line in the document on save when present.')]
        public string $title = '',

        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,

        #[Form('select', 'StakeholderNeed', help: 'Spine parent — choose this OR an approved Change Request (not both). SN also saves as @need:{code} in the Feature document.', section: 'traceability')]
        public ?int $stakeholder_need_id = null,

        #[Form('select', 'ChangeRequest', help: 'Approved CRs only — choose this OR a Stakeholder Need as parent (not both).', section: 'traceability')]
        public ?int $change_request_id = null,

        #[Form('select', 'SwimlaneFlowStep', help: 'Optional BPD process/decision step this feature elaborates (coverage only).', section: 'traceability')]
        public ?int $swimlane_flow_step_id = null,

        /**
         * Feature header document: @tags, Feature:, As a / I want / In order to, Background:.
         * Scenarios are separate records.
         */
        #[Form('code', language: 'gherkin', hideQuick: true)]
        public ?string $body = null,

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
            'stakeholder_need_id' => ['nullable', 'integer', 'exists:stakeholder_needs,id', 'required_without:change_request_id', 'prohibits:change_request_id'],
            'change_request_id' => ['nullable', 'integer', 'exists:change_requests,id', 'required_without:stakeholder_need_id', 'prohibits:stakeholder_need_id'],
            'swimlane_flow_step_id' => ['nullable', 'integer', 'exists:swimlane_flow_steps,id'],
            'body' => ['nullable', 'string'],
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
