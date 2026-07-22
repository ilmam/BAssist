<?php

namespace App\Data;

use App\Attributes\FormFieldAttribute;
use App\Attributes\ListPropertyAttribute;
use App\Attributes\ValuePropertyAttribute;

class StakeholderData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('text')]
        public string $name = '',
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('select', 'Project')]
        public int $project_id = 0,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('text')]
        public ?string $type = null,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('text')]
        public ?string $influence = null,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('text')]
        public ?string $interest = null,
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('textarea')]
        public ?string $notes = null,
        #[ListPropertyAttribute]
        #[ValuePropertyAttribute]
        #[FormFieldAttribute('select', 'Status')]
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
