<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use App\Support\NeedType;
use Illuminate\Validation\Rule;

class BusinessNeedData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[Form('text', readonly: true)]
        public ?string $code = null,

        #[Form('radio', 'NeedType')]
        public ?string $need_type = null,

        #[Form('text', uiSpan: 12)]
        #[ListForm('text')]
        public string $title = '',

        #[Form('select', 'Project', hideQuick: true)]
        public int $project_id = 0,

        #[Form('select', 'BusinessObjective', hideQuick: true, section: 'traceability')]
        public ?int $primary_business_objective_id = null,

        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,

        #[Form('textarea', hideQuick: true)]
        public ?string $rationale = null,

        #[Form('textarea', hideQuick: true)]
        public ?string $impact = null,

        #[Form('textarea', hideQuick: true)]
        public ?string $do_nothing_consequence = null,
    ) {
    }

    public static function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'need_type' => ['nullable', 'string', Rule::in(NeedType::values())],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'primary_business_objective_id' => ['nullable', 'integer', 'exists:business_objectives,id'],
        ];
    }
}
