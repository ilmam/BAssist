<?php

namespace App\Data;

use App\Attributes\InList;
use App\Attributes\Value;

class StatusViewData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[InList]
        #[Value]
        public string $name = '',
        #[InList]
        #[Value]
        public string $code = '',
        #[Value]
        public int $sort_order = 0,
        #[Value]
        public ?string $description = null,
    ) {
    }
}
