<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class RaciAssignmentViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        public int $project_id = 0,
        #[InList]
        public ?ProjectViewData $project = null,
        public int $raci_item_id = 0,
        #[InList]
        public ?RaciItemViewData $raci_item = null,
        public int $stakeholder_id = 0,
        #[InList]
        public ?StakeholderViewData $stakeholder = null,
        #[InList]
        public string $role = '',
    ) {
    }
}
