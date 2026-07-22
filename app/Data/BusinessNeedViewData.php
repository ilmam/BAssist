<?php

namespace App\Data;

use App\Attributes\InList;
use App\Attributes\Value;

class BusinessNeedViewData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[InList]
        #[Value]
        public string $title = '',
        #[Value]
        public ?string $need_type = null,
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
        public ?string $rationale = null,
        #[Value]
        public ?string $impact = null,
        #[Value]
        public ?string $do_nothing_consequence = null,
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
        public ?int $business_objectives_count = null,
        public ?int $stakeholder_needs_count = null,
        public bool $is_orphan = false,
    ) {
    }
}
