<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class ScenarioViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[InList]
        public string $title = '',
        public int $feature_id = 0,
        #[InList]
        public ?FeatureViewData $feature = null,
        #[InList]
        public bool $is_outline = false,
        #[Hide]
        public ?string $body = null,
        public ?int $status_id = null,
        #[InList]
        public ?StatusViewData $status = null,
        #[Hide]
        public ?int $project_id = null,
        #[Hide]
        public ?int $workspace_id = null,
        #[Hide]
        public ?int $tenant_id = null,
    ) {
    }
}
