<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class PriorityViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[InList]
        public string $name = '',
        #[InList]
        public string $code = '',
        public int $sort_order = 0,
        public ?string $description = null,
        public ?bool $is_system = null,
    ) {
    }
}
