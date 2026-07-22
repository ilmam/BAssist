<?php

namespace App\Data;

use App\Attributes\ListPropertyAttribute;
use App\Attributes\ValuePropertyAttribute;

class StakeholderViewData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public string $name = '',
        #[ValuePropertyAttribute]
        public int $project_id = 0,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public ?ProjectViewData $project = null,
        #[ValuePropertyAttribute]
        public ?string $type = null,
        #[ValuePropertyAttribute]
        public ?string $influence = null,
        #[ValuePropertyAttribute]
        public ?string $interest = null,
        #[ValuePropertyAttribute]
        public ?string $notes = null,
        #[ValuePropertyAttribute]
        public ?bool $is_system = null,
        #[ValuePropertyAttribute]
        public ?string $system_key = null,
        #[ValuePropertyAttribute]
        public ?int $status_id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public ?StatusViewData $status = null,
        public ?int $stakeholder_needs_count = null,
        public bool $is_orphan = false,
    ) {
    }
}
