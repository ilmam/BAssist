<?php

namespace App\Data;

use App\Attributes\Form;
use App\Attributes\ListForm;
use Illuminate\Validation\Rule;

class BusinessNeedData extends BaseData
{
    public function __construct(
        public ?int $id = null,

        #[ListForm('text')]
        public string $title = '',
        
        #[Form('text')]
        public ?string $need_type = null,
        
        #[Form('select', 'Project')]
        public int $project_id = 0,
        
        #[Form('select', 'BusinessObjective', hideQuick: true)]
        public ?int $primary_business_objective_id = null,
        
        #[Form('textarea', hideQuick: true)]
        public ?string $description = null,
        
        #[Form('textarea', hideQuick: true)]
        public ?string $rationale = null,
        
        #[Form('textarea', hideQuick: true)]
        public ?string $impact = null,
        #[Form('textarea', hideQuick: true)]
        public ?string $do_nothing_consequence = null,
        
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
            'need_type' => ['nullable', 'string', Rule::in(['problem', 'opportunity'])],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'primary_business_objective_id' => ['nullable', 'integer', 'exists:business_objectives,id'],
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
        ];
    }
}
