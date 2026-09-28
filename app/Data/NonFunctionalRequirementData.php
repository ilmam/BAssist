<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Attributes\OneOf;

#[OneOf('stakeholder_need_id', 'change_request_id')]
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

        #[Form('attachments', hideQuick: true)]
        public mixed $attachments = null,
    ) {
    }
}
