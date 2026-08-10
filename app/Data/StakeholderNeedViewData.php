<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class StakeholderNeedViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[Hide]
        public ?int $number = null,
        #[InList]
        public ?string $code = null,
        #[InList]
        public string $title = '',
        public int $project_id = 0,
        #[Hide]
        public ?int $workspace_id = null,
        #[Hide]
        public ?int $tenant_id = null,
        #[InList]
        public ?ProjectViewData $project = null,
        public ?string $description = null,
        public ?int $priority_id = null,
        #[InList]
        public ?PriorityViewData $priority = null,
        public ?int $status_id = null,
        #[InList]
        public ?StatusViewData $status = null,
        #[Hide]
        public ?int $business_objectives_count = null,
        #[Hide]
        public ?string $primary_business_objective_cell = null,
        #[Hide]
        public ?int $stakeholders_count = null,
        #[Hide]
        public bool $is_orphan = false,
    ) {
    }
}
