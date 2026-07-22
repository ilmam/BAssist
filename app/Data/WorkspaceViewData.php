<?php

namespace App\Data;

use App\Attributes\ListPropertyAttribute;
use App\Attributes\ValuePropertyAttribute;

class WorkspaceViewData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public string $name = '',
        #[ValuePropertyAttribute]
        public string $slug = '',
        #[ValuePropertyAttribute]
        public int $tenant_id = 0,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public ?TenantViewData $tenant = null,
        #[ValuePropertyAttribute]
        public ?string $description = null,
        #[ValuePropertyAttribute]
        public ?int $status_id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public ?StatusViewData $status = null,
        public ?int $projects_count = null,
        public bool $is_orphan = false,
    ) {
    }
}
