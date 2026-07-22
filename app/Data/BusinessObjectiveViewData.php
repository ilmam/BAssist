<?php

namespace App\Data;

use App\Attributes\InList;
use App\Attributes\Value;

class BusinessObjectiveViewData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[InList]
        #[Value]
        public string $title = '',
        #[Value]
        public int $project_id = 0,
        public ?int $workspace_id = null,
        public ?int $tenant_id = null,
        #[InList]
        #[Value]
        public ?ProjectViewData $project = null,
        #[Value]
        public ?string $description = null,
        #[Value]
        public ?string $success_measure = null,
        #[Value]
        public ?string $potential_value = null,
        #[Value]
        public ?int $priority_id = null,
        #[InList]
        #[Value]
        public ?PriorityViewData $priority = null,
        #[Value]
        public ?int $status_id = null,
        #[InList]
        #[Value]
        public ?StatusViewData $status = null,
        public ?int $business_needs_count = null,
        public bool $is_orphan = false,
    ) {
    }
}
