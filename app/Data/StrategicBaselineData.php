<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Support\StrategicBaselineStatus;
use Illuminate\Validation\Rule;

class StrategicBaselineData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[Form('select', 'Project')]
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

    public static function rules()
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'current_state' => ['nullable', 'string'],
            'future_state' => ['nullable', 'string'],
            'change_strategy' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(StrategicBaselineStatus::values())],
        ];
    }
}
