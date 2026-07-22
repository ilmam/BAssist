<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Attributes\Value;

class StakeholderData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        #[Value]
        public string $name = '',
        #[Form('select', 'Project')]
        #[Value]
        public int $project_id = 0,
        #[Form('text')]
        #[Value]
        public ?string $type = null,
        #[Form('text', hideQuick: true)]
        #[Value]
        public ?string $influence = null,
        #[Form('text', hideQuick: true)]
        #[Value]
        public ?string $interest = null,
        #[Form('textarea', hideQuick: true)]
        #[Value]
        public ?string $notes = null,
        #[ListForm('select', 'Status', hideQuick: true)]
        #[Value]
        public ?int $status_id = null,
    ) {
    }

    public static function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
