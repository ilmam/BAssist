<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class ArchitectureViewData extends BaseData
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
        public ?array $elements = null,
        #[Hide]
        public ?array $relationships = null,
        #[Hide]
        public ?array $layout = null,
        public ?int $status_id = null,
        #[InList]
        public ?StatusViewData $status = null,
    ) {
    }
}
