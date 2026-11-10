<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class RaciItemData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text', help: 'Activity or deliverable, e.g. Requirements sign-off or UAT.')]
        public string $title = '',
        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,
        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,
    ) {
    }
}
