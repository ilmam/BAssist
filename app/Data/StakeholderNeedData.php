<?php

namespace App\Data;

use App\Attributes\FormFieldAttribute;
use App\Attributes\ListPropertyAttribute;
use App\Attributes\ValuePropertyAttribute;

class StakeholderNeedData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('text')]
        public string $title = '',
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('select', 'Project')]
        public int $project_id = 0,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('select', 'BusinessNeed')]
        public int $business_need_id = 0,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('select', 'Stakeholder')]
        public int $stakeholder_id = 0,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('textarea')]
        public ?string $description = null,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('select', 'Priority')]
        public ?int $priority_id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('select', 'Status')]
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
