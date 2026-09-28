<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class TenantData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        public string $name = '',
        #[Form('text')]
        public string $slug = '',
        #[ListForm('select', 'Status')]
        public ?int $status_id = null,
    ) {
    }
}
