<?php

namespace App\Data;

use App\Attributes\ListPropertyAttribute;
use App\Attributes\ValuePropertyAttribute;

class StakeholderNeedViewData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public string $title = '',
        #[ValuePropertyAttribute]
        public int $project_id = 0,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public ?ProjectViewData $project = null,
        #[ValuePropertyAttribute]
        public ?string $description = null,
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
        public ?int $business_needs_count = null,
        public ?int $stakeholders_count = null,
        public bool $is_orphan = false,
    ) {
    }
}
