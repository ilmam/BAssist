<?php

namespace App\Data;

use App\Attributes\InList;
use App\Attributes\Value;

class WorkspaceViewData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[InList]
        #[Value]
        public string $name = '',
        #[Value]
        public string $slug = '',
        #[Value]
        public int $tenant_id = 0,
        #[InList]
        #[Value]
        public ?TenantViewData $tenant = null,
        #[Value]
        public ?string $description = null,
        #[Value]
        public ?int $status_id = null,
        #[InList]
        #[Value]
        public ?StatusViewData $status = null,
        public ?int $projects_count = null,
        public bool $is_orphan = false,
    ) {
    }
}
