<?php

namespace App\Data;

use App\Attributes\FormFieldAttribute;
use App\Attributes\ListPropertyAttribute;
use App\Attributes\ValuePropertyAttribute;

class BusinessObjectiveData extends BaseData
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
        #[FormFieldAttribute('textarea')]
        public ?string $description = null,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('textarea')]
        public ?string $success_measure = null,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('textarea')]
        public ?string $potential_value = null,
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
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
