<?php

namespace App\Data;

use App\Attributes\FormFieldAttribute;
use App\Attributes\ListPropertyAttribute;
use App\Attributes\ValuePropertyAttribute;
use Illuminate\Validation\Rule;

class BusinessNeedData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('text')]
        public string $title = '',
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('text')]
        public ?string $need_type = null,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('select', 'Project')]
        public int $project_id = 0,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('select', 'BusinessObjective')]
        public ?int $primary_business_objective_id = null,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('textarea')]
        public ?string $description = null,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('textarea')]
        public ?string $rationale = null,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('textarea')]
        public ?string $impact = null,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('textarea')]
        public ?string $do_nothing_consequence = null,
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
            'need_type' => ['nullable', 'string', Rule::in(['problem', 'opportunity'])],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'primary_business_objective_id' => ['nullable', 'integer', 'exists:business_objectives,id'],
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
