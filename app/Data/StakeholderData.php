<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class StakeholderData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        public string $name = '',
        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,
        #[Form('select', 'StakeholderType', help: 'The kind of stakeholder, listed from outside the organization inward (BABOK stakeholder onion diagram).')]
        public ?string $type = null,
        #[Form('select', 'StakeholderLevel', hideQuick: true, help: 'Ability to affect the initiative or its outcome (BABOK stakeholder matrix).')]
        public ?string $influence = null,
        #[Form('select', 'StakeholderLevel', hideQuick: true, help: 'How much the stakeholder is concerned with or affected by the initiative.')]
        public ?string $interest = null,
        #[Form('textarea', hideQuick: true)]
        public ?string $notes = null,
        #[ListForm('select', 'Status', hideQuick: true)]
        public ?int $status_id = null,
    ) {
    }
}
