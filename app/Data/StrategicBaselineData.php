<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Support\StrategicBaselineStatus;

class StrategicBaselineData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,

        #[Form('textarea', hideQuick: true)]
        public ?string $current_state = null,

        #[Form('textarea', hideQuick: true)]
        public ?string $future_state = null,

        #[Form('textarea', hideQuick: true)]
        public ?string $change_strategy = null,

        #[ListForm('select', 'StrategicBaselineStatus')]
        public string $status = StrategicBaselineStatus::DRAFT,
    ) {
    }
}
