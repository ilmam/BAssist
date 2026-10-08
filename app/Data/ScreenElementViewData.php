<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class ScreenElementViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        public int $screen_id = 0,
        #[InList]
        public ?ScreenViewData $screen = null,
        public ?int $parent_id = null,
        public int $project_id = 0,
        #[Hide]
        public ?int $workspace_id = null,
        #[Hide]
        public ?int $tenant_id = null,
        #[InList]
        public int $position = 0,
        public ?int $row = null,
        #[InList]
        public string $kind = '',
        #[InList]
        public string $label = '',
        public ?int $functional_requirement_id = null,
    ) {
    }
}
