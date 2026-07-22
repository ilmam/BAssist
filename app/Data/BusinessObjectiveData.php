<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Attributes\Value;

class BusinessObjectiveData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        #[Value]
        public string $title = '',
        #[Form('select', 'Project')]
        #[Value]
        public int $project_id = 0,
        #[Form('textarea', hideQuick: true)]
        #[Value]
        public ?string $description = null,
        #[Form('textarea', hideQuick: true)]
        #[Value]
        public ?string $success_measure = null,
        #[Form('textarea', hideQuick: true)]
        #[Value]
        public ?string $potential_value = null,
        #[Form('select', 'Priority')]
        #[Value]
        public ?int $priority_id = null,
        #[ListForm('select', 'Status', hideQuick: true)]
        #[Value]
        public ?int $status_id = null,
    ) {
    }

    public static function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
