<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class WorkspaceData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        public string $name = '',
        #[Form('text')]
        public string $slug = '',
        #[Form('select', 'Tenant')]
        public int $tenant_id = 0,
        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,
        #[ListForm('select', 'Status', hideQuick: true)]
        public ?int $status_id = null,
    ) {
    }
}
