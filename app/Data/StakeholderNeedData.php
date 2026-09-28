<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class StakeholderNeedData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[Form('text', readonly: true)]
        public ?string $code = null,
        #[ListForm('text')]
        public string $title = '',
        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,
        #[Form('select', 'BusinessObjective', section: 'traceability')]
        public int $business_objective_id = 0,
        #[Form('select', 'Stakeholder', section: 'traceability')]
        public int $stakeholder_id = 0,
        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,
        #[Form('select', 'Priority')]
        public ?int $priority_id = null,
        #[ListForm('select', 'Status', hideQuick: true)]
        public ?int $status_id = null,

        #[Form('attachments', hideQuick: true)]
        public mixed $attachments = null,
    ) {
    }
}
