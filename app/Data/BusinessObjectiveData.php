<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;

class BusinessObjectiveData extends BaseData
{
    public function __construct(
        public ?int $id = null,
        #[Form('text', readonly: true)]
        public ?string $code = null,
        #[ListForm('text')]
        public string $title = '',
        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,
        #[Form('select', 'BusinessNeed', hideQuick: true, section: 'traceability')]
        public ?int $primary_business_need_id = null,
        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,
        #[Form('text', hideQuick: true)]
        public ?string $success_measure = null,
        #[Form('text', hideQuick: true)]
        public ?string $potential_value = null,
    ) {
    }

    public static function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'primary_business_need_id' => ['nullable', 'integer', 'exists:business_needs,id'],
        ];
    }
}
