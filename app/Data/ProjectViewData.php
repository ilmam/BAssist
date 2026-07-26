<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class ProjectViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[InList]
        public string $name = '',
        public ?string $code = null,
        public int $workspace_id = 0,
        #[Hide]
        public ?int $tenant_id = null,
        #[InList]
        public ?WorkspaceViewData $workspace = null,
        public ?string $description = null,
        public ?int $status_id = null,
        #[InList]
        public ?StatusViewData $status = null,
        #[Hide]
        public ?int $business_objectives_count = null,
        #[Hide]
        public ?int $business_needs_count = null,
        #[Hide]
        public ?int $stakeholders_count = null,
        #[Hide]
        public ?int $stakeholder_needs_count = null,
        #[Hide]
        public ?int $features_count = null,
        #[Hide]
        public ?int $state_flows_count = null,
        #[Hide]
        public ?int $swimlane_flows_count = null,
        #[Hide]
        public bool $is_orphan = false,
    ) {
    }
}
