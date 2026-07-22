<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class BusinessNeedViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[InList]
        public string $title = '',
        public ?string $need_type = null,
        public int $project_id = 0,
        #[Hide]
        public ?int $workspace_id = null,
        #[Hide]
        public ?int $tenant_id = null,
        #[InList]
        public ?ProjectViewData $project = null,
        public ?string $description = null,
        public ?string $rationale = null,
        public ?string $impact = null,
        public ?string $do_nothing_consequence = null,
        public ?int $priority_id = null,
        #[InList]
        public ?PriorityViewData $priority = null,
        public ?int $status_id = null,
        #[InList]
        public ?StatusViewData $status = null,
        #[Hide]
        public ?int $business_objectives_count = null,
        #[Hide]
        public ?int $stakeholder_needs_count = null,
        #[Hide]
        public bool $is_orphan = false,
    ) {
    }
}
