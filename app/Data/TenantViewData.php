<?php

namespace App\Data;

use App\Attributes\InList;
use App\Attributes\Value;

class TenantViewData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[InList]
        #[Value]
        public string $name = '',
        #[Value]
        public string $slug = '',
        #[Value]
        public ?int $status_id = null,
        #[InList]
        #[Value]
        public ?StatusViewData $status = null,
    ) {
    }
}
