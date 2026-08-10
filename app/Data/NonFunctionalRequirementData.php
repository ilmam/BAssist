<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Support\NfrCategory;
use Illuminate\Validation\Rule;

class NonFunctionalRequirementData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[Form('text', readonly: true)]
        public ?string $code = null,

        #[ListForm('text')]
        public string $title = '',

        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,

        #[Form('select', 'StakeholderNeed', help: 'Spine parent — choose this OR an approved Change Request (not both).', section: 'traceability', uiSpan: 12)]
        public ?int $stakeholder_need_id = null,

        #[Form('select', 'ChangeRequest', help: 'Approved CRs only — choose this OR a Stakeholder Need as parent (not both).', section: 'traceability')]
        public ?int $change_request_id = null,

        #[Form('select', 'NfrCategory')]
        #[ListForm('select', 'NfrCategory')]
        public string $category = '',

        #[Form('textarea', hideQuick: true)]
        public string $description = '',

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
            'stakeholder_need_id' => ['nullable', 'integer', 'exists:stakeholder_needs,id', 'required_without:change_request_id', 'prohibits:change_request_id'],
            'change_request_id' => ['nullable', 'integer', 'exists:change_requests,id', 'required_without:stakeholder_need_id', 'prohibits:stakeholder_need_id'],
            'category' => ['required', 'string', Rule::in(NfrCategory::values())],
            'description' => ['required', 'string'],
            'acceptance_criteria' => ['nullable', 'string'],
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
