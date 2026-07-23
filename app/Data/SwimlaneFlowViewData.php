<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class SwimlaneFlowViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[InList]
        public string $title = '',
        public int $project_id = 0,
        #[Hide]
        public ?int $workspace_id = null,
        #[Hide]
        public ?int $tenant_id = null,
        #[InList]
        public ?ProjectViewData $project = null,
        public ?string $description = null,
        #[Hide]
        public string $direction = 'TB',
        #[Hide]
        public ?array $elements = null,
        public ?int $status_id = null,
        #[InList]
        public ?StatusViewData $status = null,
    ) {
    }
}
