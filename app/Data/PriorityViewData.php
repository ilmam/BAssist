<?php

namespace App\Data;

use App\Attributes\ListPropertyAttribute;
use App\Attributes\ValuePropertyAttribute;

class PriorityViewData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public string $name = '',
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        public string $code = '',
        #[ValuePropertyAttribute]
        public int $sort_order = 0,
        #[ValuePropertyAttribute]
        public ?string $description = null,
    ) {
    }
}
