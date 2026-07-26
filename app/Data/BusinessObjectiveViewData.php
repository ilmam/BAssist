<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class BusinessObjectiveViewData extends BaseData
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
        public ?string $success_measure = null,
        public ?string $potential_value = null,
        public ?int $priority_id = null,
        #[InList]
        public ?PriorityViewData $priority = null,
        public ?int $status_id = null,
        #[InList]
        public ?StatusViewData $status = null,
        #[Hide]
        public ?int $business_needs_count = null,
        #[Hide]
        public bool $is_orphan = false,
    ) {
    }
}
