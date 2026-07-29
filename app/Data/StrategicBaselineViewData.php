<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class StrategicBaselineViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        public int $project_id = 0,
        #[InList]
        public ?ProjectViewData $project = null,
        public ?string $current_state = null,
        public ?string $future_state = null,
        public ?string $change_strategy = null,
        #[InList]
        public string $status = '',
    ) {
    }
}
