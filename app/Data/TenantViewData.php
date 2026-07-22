<?php

namespace App\Data;

use App\Attributes\Hide;
use App\Attributes\InList;

class TenantViewData extends BaseData
{
    public function __construct(
        #[Hide]
        public ?int $id = null,
        #[InList]
        public string $name = '',
        public string $slug = '',
        public ?int $status_id = null,
        #[InList]
        public ?StatusViewData $status = null,
    ) {
    }
}
