<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class StakeholderNeedData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListForm('text')]
        public string $title = '',
        #[Form('select', 'Project')]
        public int $project_id = 0,
        #[Form('select', 'BusinessNeed')]
        public int $business_need_id = 0,
        #[Form('select', 'Stakeholder')]
        public int $stakeholder_id = 0,
        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,
        #[Form('select', 'Priority')]
        public ?int $priority_id = null,
        #[ListForm('select', 'Status', hideQuick: true)]
        public ?int $status_id = null,
    ) {
    }

    public static function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'business_need_id' => ['required', 'integer', 'exists:business_needs,id'],
            'stakeholder_id' => ['required', 'integer', 'exists:stakeholders,id'],
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
