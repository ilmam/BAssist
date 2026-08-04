<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class RiskViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[Hide]
        public ?int $number = null,
        #[InList]
        public ?string $code = null,
        #[InList]
        public string $title = '',
        public int $project_id = 0,
        #[InList]
        public ?ProjectViewData $project = null,
        public ?string $description = null,
        #[InList]
        public string $category = '',
        #[InList]
        public string $likelihood = '',
        #[InList]
        public string $impact = '',
        #[InList]
        public ?string $score_label = null,
        #[Hide]
        public ?int $score = null,
        #[Hide]
        public ?string $score_band = null,
        #[InList]
        public ?string $response = null,
        public ?string $treatment = null,
        public ?string $trigger = null,
        #[InList]
        public ?string $owner = null,
        #[InList]
        public string $status = '',
        public ?string $source = null,
        #[InList]
        public ?string $related_to = null,
        #[Hide]
        public bool $is_critical = false,
        #[Hide]
        public bool $has_coverage_gap = false,
    ) {
    }
}
