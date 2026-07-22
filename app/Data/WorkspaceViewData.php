<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class WorkspaceViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[InList]
        public string $name = '',
        public string $slug = '',
        public int $tenant_id = 0,
        #[InList]
        public ?TenantViewData $tenant = null,
        public ?string $description = null,
        public ?int $status_id = null,
        #[InList]
        public ?StatusViewData $status = null,
        #[Hide]
        public ?int $projects_count = null,
        #[Hide]
        public bool $is_orphan = false,
    ) {
    }
}
