<?php

namespace App\Data;

use App\Attributes\InList;
use App\Attributes\Value;

class StakeholderViewData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[InList]
        #[Value]
        public string $name = '',
        #[Value]
        public int $project_id = 0,
        public ?int $workspace_id = null,
        public ?int $tenant_id = null,
        #[InList]
        #[Value]
        public ?ProjectViewData $project = null,
        #[Value]
        public ?string $type = null,
        #[Value]
        public ?string $influence = null,
        #[Value]
        public ?string $interest = null,
        #[Value]
        public ?string $notes = null,
        #[Value]
        public ?bool $is_system = null,
        #[Value]
        public ?string $system_key = null,
        #[Value]
        public ?int $status_id = null,
        #[InList]
        #[Value]
        public ?StatusViewData $status = null,
        public ?int $stakeholder_needs_count = null,
        public bool $is_orphan = false,
    ) {
    }
}
