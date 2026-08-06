<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Support\ChangeRequestImpact;
use App\Support\ChangeRequestStatus;
use Illuminate\Validation\Rule;

class ChangeRequestData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[Form('text', readonly: true)]
        public ?string $code = null,

        #[ListForm('text')]
        public string $title = '',

        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,

        #[Form('textarea', hideQuick: true, help: 'What is wrong or missing in the current behaviour? Why is change needed?')]
        public string $problem = '',

        #[Form('textarea', hideQuick: true, help: 'What should change? Describe the desired outcome, not the UI design.')]
        public string $proposed_change = '',

        #[ListForm('text', help: 'Who requested this change (name or role). Not necessarily the logged-in user.')]
        public string $requestor = '',

        #[ListForm('select', 'ChangeRequestImpact')]
        public string $impact_level = ChangeRequestImpact::MEDIUM,

        #[Form('textarea', hideQuick: true, help: 'Brief impact notes. Required when impact is High.')]
        public ?string $impact_notes = null,

        #[ListForm('select', 'StakeholderNeed', help: 'Anchor this change to a Stakeholder Need (5 Whys). Create the SN first if this change invents a new need.', section: 'traceability')]
        public ?int $stakeholder_need_id = null,

        #[Form('select', 'Priority')]
        public ?int $priority_id = null,

        #[ListForm('select', 'ChangeRequestStatus', help: 'Use Approve & mark for revision to move to Approved (confirms which FR/BDD to taint).')]
        public string $status = ChangeRequestStatus::DRAFT,
    ) {
    }

    public static function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'problem' => ['required', 'string'],
            'proposed_change' => ['required', 'string'],
            'requestor' => ['required', 'string', 'max:255'],
            'impact_level' => ['required', 'string', Rule::in(ChangeRequestImpact::values())],
            'impact_notes' => ['nullable', 'string'],
            'stakeholder_need_id' => ['nullable', 'integer', 'exists:stakeholder_needs,id'],
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'status' => ['required', 'string', Rule::in(ChangeRequestStatus::values())],
        ];
    }
}
