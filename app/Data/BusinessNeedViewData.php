<?php

namespace App\Data;

use App\Attributes\ListPropertyAttribute;
use App\Attributes\ValuePropertyAttribute;

class BusinessNeedViewData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public string $title = '',
        #[ValuePropertyAttribute]
        public ?string $need_type = null,
        #[ValuePropertyAttribute]
        public int $project_id = 0,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public ?ProjectViewData $project = null,
        #[ValuePropertyAttribute]
        public ?string $description = null,
        #[ValuePropertyAttribute]
        public ?string $rationale = null,
        #[ValuePropertyAttribute]
        public ?string $impact = null,
        #[ValuePropertyAttribute]
        public ?string $do_nothing_consequence = null,
        #[ValuePropertyAttribute]
        public ?int $priority_id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public ?PriorityViewData $priority = null,
        #[ValuePropertyAttribute]
        public ?int $status_id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public ?StatusViewData $status = null,
        public ?int $business_objectives_count = null,
        public ?int $stakeholder_needs_count = null,
        public bool $is_orphan = false,
    ) {
    }
}
