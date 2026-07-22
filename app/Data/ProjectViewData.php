<?php

namespace App\Data;

use App\Attributes\ListPropertyAttribute;
use App\Attributes\ValuePropertyAttribute;

class ProjectViewData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public string $name = '',
        #[ValuePropertyAttribute]
        public ?string $code = null,
        #[ValuePropertyAttribute]
        public int $workspace_id = 0,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public ?WorkspaceViewData $workspace = null,
        #[ValuePropertyAttribute]
        public ?string $description = null,
        #[ValuePropertyAttribute]
        public ?int $status_id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public ?StatusViewData $status = null,
        public ?int $business_objectives_count = null,
        public ?int $business_needs_count = null,
        public ?int $stakeholders_count = null,
        public ?int $stakeholder_needs_count = null,
        public bool $is_orphan = false,
    ) {
    }
}
