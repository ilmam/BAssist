<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class RaciAssignmentData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,
        #[ListForm('select', 'RaciItem')]
        public int $raci_item_id = 0,
        #[ListForm('select', 'Stakeholder')]
        public int $stakeholder_id = 0,
        #[ListForm('select', 'RaciRole', help: 'Only one stakeholder can be Accountable (A) for an item.')]
        public string $role = '',
    ) {
    }
}
