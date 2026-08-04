<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class ChangeRequestViewData extends BaseData
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
        public string $problem = '',
        public string $proposed_change = '',
        #[InList]
        public string $requestor = '',
        #[InList]
        public string $impact_level = '',
        public ?string $impact_notes = null,
        #[InList]
        public ?string $affected_type = null,
        public ?int $affected_id = null,
        #[InList]
        public ?string $affected_label = null,
        public ?int $priority_id = null,
        #[InList]
        public ?PriorityViewData $priority = null,
        #[InList]
        public string $status = '',
    ) {
    }
}
