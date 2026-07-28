<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class BusinessRuleViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[InList]
        public string $title = '',
        public int $project_id = 0,
        #[InList]
        public ?ProjectViewData $project = null,
        public ?string $description = null,
        #[InList]
        public string $status = '',
        public ?string $source = null,
    ) {
    }
}
