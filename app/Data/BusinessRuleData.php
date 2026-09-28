<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Support\BusinessRuleStatus;

class BusinessRuleData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        public string $title = '',
        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,
        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,
        #[ListForm('select', 'BusinessRuleStatus')]
        public string $status = BusinessRuleStatus::DRAFT,
        #[Form('text', hideQuick: true)]
        public ?string $source = null,
    ) {
    }
}
