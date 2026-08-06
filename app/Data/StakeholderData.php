<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class StakeholderData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        public string $name = '',
        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,
        #[Form('text')]
        public ?string $type = null,
        #[Form('text', hideQuick: true)]
        public ?string $influence = null,
        #[Form('text', hideQuick: true)]
        public ?string $interest = null,
        #[Form('textarea', hideQuick: true)]
        public ?string $notes = null,
        #[ListForm('select', 'Status', hideQuick: true)]
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
