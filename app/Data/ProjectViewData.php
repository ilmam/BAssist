<?php

namespace App\Data;

use App\Attributes\InList;
use App\Attributes\Value;

class ProjectViewData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[InList]
        #[Value]
        public string $name = '',
        #[Value]
        public ?string $code = null,
        #[Value]
        public int $workspace_id = 0,
        public ?int $tenant_id = null,
        #[InList]
        #[Value]
        public ?WorkspaceViewData $workspace = null,
        #[Value]
        public ?string $description = null,
        #[Value]
        public ?int $status_id = null,
        #[InList]
        #[Value]
        public ?StatusViewData $status = null,
        public ?int $business_objectives_count = null,
        public ?int $business_needs_count = null,
        public ?int $stakeholders_count = null,
        public ?int $stakeholder_needs_count = null,
        public bool $is_orphan = false,
    ) {
    }
}
