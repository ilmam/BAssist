<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class StakeholderViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[InList]
        public string $name = '',
        public int $project_id = 0,
        #[Hide]
        public ?int $workspace_id = null,
        #[Hide]
        public ?int $tenant_id = null,
        #[InList]
        public ?ProjectViewData $project = null,
        public ?string $type = null,
        public ?string $influence = null,
        public ?string $interest = null,
        public ?string $notes = null,
        public ?bool $is_system = null,
        public ?string $system_key = null,
        public ?int $status_id = null,
        #[InList]
        public ?StatusViewData $status = null,
        #[Hide]
        public ?int $stakeholder_needs_count = null,
        #[Hide]
        public bool $is_orphan = false,
    ) {
    }
}
