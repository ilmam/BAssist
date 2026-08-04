<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Support\ChangeRequestAffectedType;
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

        #[Form('select', 'Project')]
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

        #[ListForm('select', 'ChangeRequestAffectedType', help: 'Level of the requirement this change applies to.')]
        public ?string $affected_type = null,

        #[Form('select', help: 'The specific requirement being changed. Choose the type first.')]
        public ?int $affected_id = null,

        #[Form('select', 'Priority')]
        public ?int $priority_id = null,

        #[ListForm('select', 'ChangeRequestStatus')]
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
            'affected_type' => ['nullable', 'string', Rule::in(ChangeRequestAffectedType::values())],
            'affected_id' => ['nullable', 'integer', 'min:1'],
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'status' => ['required', 'string', Rule::in(ChangeRequestStatus::values())],
        ];
    }
}
