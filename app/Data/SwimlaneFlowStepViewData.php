<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class SwimlaneFlowStepViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[Hide]
        public ?int $number = null,
        #[InList]
        public ?string $code = null,
        #[InList]
        public string $label = '',
        public ?string $lane = null,
        public ?string $type = null,
    ) {
    }
}
