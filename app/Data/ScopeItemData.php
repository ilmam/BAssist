<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Support\ScopeItemDirection;

class ScopeItemData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[ListForm('text')]
        public string $title = '',

        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,

        #[ListForm('select', 'ScopeItemDirection')]
        public string $direction = ScopeItemDirection::IN,

        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,
    ) {
    }
}
